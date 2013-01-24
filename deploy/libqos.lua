------------------------------------------------------------------------------
--
-- ClearOS Firewall
--
-- External QoS Manager.
-- See /etc/clearos/qos.conf for configuration settings.
--
------------------------------------------------------------------------------
--
-- Copyright (C) 2012-2013 ClearFoundation
-- 
-- This program is free software; you can redistribute it and/or
-- modify it under the terms of the GNU General Public License
-- as published by the Free Software Foundation; either version 2
-- of the License, or (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

------------------------------------------------------------------------------
--
-- F U N C T I O N S
--
------------------------------------------------------------------------------

function PackCustomRule(s)
    local c = 1

    while c > 0 do
        s, c = string.gsub(s, "  ", " ")
    end

    c = 1
    while c > 0 do
        s, c = string.gsub(s, "\t", "")
    end

    c = 1
    while c > 0 do
        s, c = string.gsub(s, "\n\n", "\n")
    end

    c = 1
    while c > 0 do
        s, c = string.gsub(s, "\n ", "\n")
    end

    if string.sub(s, 1, 1) == " " then
        s = string.sub(s, 2)
    end

    if string.sub(s, string.len(s)) == " " then
        s = string.sub(s, 1, string.len(s) - 1)
    end

    return s .. "\n"
end

------------------------------------------------------------------------------
--
-- RunBandwidthExternal
--
-- Initialize QoS classes, add custom priority rules.
--
------------------------------------------------------------------------------

function RunBandwidthExternal()
    local ifn
    local imq_id = 0
    local ifn_id = 0
    local dev_qlen = 30
    local rate_up = {}
    local rate_down = {}
    local rule
    local rules
    local param
    local priomark = { ipv4={}, ipv6={}, custom={} }

    echo("Running external QoS bandwidth manager")

    if QOS_PRIOMARK4 ~= nil then
        rules = Explode(" ",
            string.gsub(PackWhitespace(QOS_PRIOMARK4), "\t", ""))
        for _, rule in pairs(rules) do
            if rule ~= nil and string.len(rule) ~= 0 then
                rule = Explode("|", rule)
                table.insert(priomark.ipv4, {
                    name=rule[1], type=rule[2],
                    prio=rule[3], proto=rule[4],
                    saddr=rule[5], sport=rule[6],
                    daddr=rule[7], dport=rule[8]
                })
            end
        end
    end

    if QOS_PRIOMARK6 ~= nil then
        rules = Explode(" ",
            string.gsub(PackWhitespace(QOS_PRIOMARK6), "\t", ""))
        for _, rule in pairs(rules) do
            if rule ~= nil and string.len(rule) ~= 0 then
                rule = Explode("|", rule)
                table.insert(priomark.ipv6, {
                    name=rule[1], type=rule[2],
                    prio=rule[3], proto=rule[4],
                    saddr=rule[5], sport=rule[6],
                    daddr=rule[7], dport=rule[8]
                })
            end
        end
    end

    if QOS_PRIOMARK_CUSTOM ~= nil then
        rules = Explode("\n", PackCustomRule(QOS_PRIOMARK_CUSTOM))
        for _, rule in pairs(rules) do
            if rule ~= nil and string.len(rule) ~= 0 then
                rule = Explode("|", rule)
                table.insert(priomark.custom, {
                    name=rule[1], type=rule[2],
                    prio=rule[3], param=rule[4]
                })
            end
        end
    end

    for _, ifn in pairs(WANIF_CONFIG) do
        execute(TCBIN .. " qdisc del dev " .. ifn .. " root >/dev/null 2>&1")
        execute(TCBIN .. " qdisc del dev imq" .. imq_id .. " root >/dev/null 2>&1")
        execute(IPBIN .. " link set imq" .. imq_id .. " down 2>/dev/null 2>&1")
        imq_id = imq_id + 1
    end

    execute(string.format("%s %s >/dev/null 2>&1", RMMOD, "imq"))

    rate_up = ParseBandwidthVariable(QOS_UPSTREAM)
    rate_down = ParseBandwidthVariable(QOS_DOWNSTREAM)

    for _, ifn in pairs(WANIF_CONFIG) do
        iptc_create_chain("mangle", "BWQOS_UP_" .. ifn)
        execute(IPBIN .. " link set dev " .. ifn .. " qlen 30")
        execute(TCBIN .. " qdisc add dev " .. ifn ..
            " root handle 1: htb default " .. (ifn_id + 1) .. "6")
        execute(TCBIN .. " class add dev " .. ifn ..
            " parent 1: classid 1:1 htb rate " .. rate_up[ifn] .. "kbit")
        for i = 0, 6 do
            execute(TCBIN .. " class add dev " .. ifn ..
                " parent 1:1 classid 1:" ..
                (ifn_id + 1) .. i .. " htb rate " ..
                (rate_up[ifn] / 7) .. "kbit ceil " .. rate_up[ifn] ..
                "kbit prio " .. i)
            execute(TCBIN .. " qdisc add dev " .. ifn ..
                " parent 1:" .. (ifn_id + 1) .. i ..
                " handle " .. (ifn_id + 1) .. i ..
                ": sfq perturb 10")
            execute(TCBIN .. " filter add dev " .. ifn ..
                " parent 1:0 prio 0 protocol ip handle " ..
                (ifn_id + 1) .. i ..
                " fw flowid 1:" .. (ifn_id + 1) .. i)
        end

        -- All ICMP: highest priority
        iptables("mangle", "-A BWQOS_UP_" .. ifn ..
            " -p icmp -j MARK " ..
            "--set-mark " .. (ifn_id + 1) .. "0")

        -- All UDP: high priority
        iptables("mangle", "-A BWQOS_UP_" .. ifn ..
            " -p udp -j MARK " ..
            "--set-mark " .. (ifn_id + 1) .. "1")

        -- Small TCP packets (probably ACKs): high priority
        iptables("mangle", "-A BWQOS_UP_" .. ifn ..
            " -p tcp -m length --length :64 -j MARK " ..
            "--set-mark " .. (ifn_id + 1) .. "1")

        -- Add user-defined MARK rules here
        for _, rule in ipairs(priomark.ipv4) do
            param = " "
            if tonumber(rule.type) == 0 then
                if rule.proto ~= "-" then
                    if string.sub(rule.proto, 1, 1) == "!" then
                        param = param .. "! -p " ..
                            string.sub(rule.proto, 2) .. " "
                    else
                        param = param .. "-p " .. rule.proto .. " "
                    end
                end
                if rule.saddr ~= "-" then
                    param = param .. "-s" .. rule.saddr .. " "
                end
                if rule.sport ~= "-" then
                    param = param .. "--sport " .. rule.sport .. " "
                end
                if rule.daddr ~= "-" then
                    param = param .. "-d " .. rule.daddr .. " "
                end
                if rule.dport ~= "-" then
                    param = param .. "--dport " .. rule.dport .. " "
                end

                iptables("mangle", "-A BWQOS_UP_" .. ifn .. param ..
                    "-j MARK --set-mark " .. (ifn_id + 1) .. rule.prio)
            end
        end

        for _, rule in ipairs(priomark.custom) do
            param = " "
            if tonumber(rule.type) == 0 then
                iptables("mangle", "-A BWQOS_UP_" .. ifn .. 
                    " " .. rule.param ..
                    " -j MARK --set-mark " .. (ifn_id + 1) .. rule.prio)
            end
        end

        iptables("mangle", "-I POSTROUTING -o " .. ifn ..
            " -j BWQOS_UP_" .. ifn)

        ifn_id = ifn_id + 1
    end

    execute(string.format("%s %s numdevs=%d",
        MODPROBE, "imq",
        TableCount(WANIF_CONFIG)))

    imq_id = 0
    for _, ifn in pairs(WANIF_CONFIG) do
        execute(IPBIN .. " link set imq" .. imq_id .. " up")

        execute(TCBIN .. " qdisc add dev imq" .. imq_id ..
            " handle 1: root htb default " .. (imq_id + 1) .. "1")

        execute(TCBIN .. " class add dev imq" .. imq_id ..
            " parent 1: classid 1:1 htb rate " .. rate_down[ifn] .. "kbit")
        for i = 0, 1 do
            execute(TCBIN .. " class add dev imq" .. imq_id ..
                " parent 1:1 classid 1:" .. (imq_id + 1) .. i ..
                " htb rate " .. (rate_down[ifn] / 2) .. "kbit" ..
                " ceil " .. rate_down[ifn] .. "kbit prio " .. i)
        end

        execute(TCBIN .. " qdisc add dev imq" .. imq_id ..
            " parent 1:" .. (imq_id + 1) .. "0 handle " ..
            (imq_id + 1) .. "0: sfq perturb 10")
        execute(TCBIN .. " qdisc add dev imq" .. imq_id ..
            " parent 1:" .. (imq_id + 1) .. "1 handle " ..
            (imq_id + 1) .. "1: red limit 1000000 min 5000 max 100000 avpkt 1000 burst 50")

        execute(TCBIN .. " filter add dev imq" .. imq_id ..
            " parent 1:0 prio 0 protocol ip handle " ..
            (imq_id + 1) .. "0 fw flowid 1:" .. (imq_id + 1) .. "0")
        execute(TCBIN .. " filter add dev imq" .. imq_id ..
            " parent 1:0 prio 0 protocol ip handle " ..
            (imq_id + 1) .. "1 fw flowid 1:" .. (imq_id + 1) .. "1")

        iptc_create_chain("mangle", "BWQOS_DOWN_" .. ifn)

        iptables("mangle",
            "-I PREROUTING -i " .. ifn .. " -j BWQOS_DOWN_" .. ifn)

        iptables("mangle", "-A BWQOS_DOWN_" .. ifn ..
            " ! -p tcp -j MARK --set-mark " ..  (imq_id + 1) .. "0")
        iptables("mangle", "-A BWQOS_DOWN_" .. ifn ..
            " -p tcp -m length --length :64 -j MARK --set-mark " ..
            (imq_id + 1) .. "0")

        -- Add user-defined MARK rules here
        for _, rule in ipairs(priomark.ipv4) do
            param = " "
            if tonumber(rule.type) == 1 then
                if rule.proto ~= "-" then
                    param = param .. "-p " .. rule.proto .. " "
                end
                if rule.saddr ~= "-" then
                    param = param .. "-s" .. rule.saddr .. " "
                end
                if rule.sport ~= "-" then
                    param = param .. "--sport " .. rule.sport .. " "
                end
                if rule.daddr ~= "-" then
                    param = param .. "-d " .. rule.daddr .. " "
                end
                if rule.dport ~= "-" then
                    param = param .. "--dport " .. rule.dport .. " "
                end

                iptables("mangle", "-A BWQOS_DOWN_" .. ifn ..  param ..
                    "-j MARK --set-mark " .. (imq_id + 1) .. rule.prio)
            end
        end

        for _, rule in ipairs(priomark.custom) do
            param = " "
            if tonumber(rule.type) == 1 then
                iptables("mangle", "-A BWQOS_DOWN_" .. ifn .. 
                    " " .. rule.param ..
                    " -j MARK --set-mark " .. (imq_id + 1) .. rule.prio)
            end
        end

        iptables("mangle",
            "-A BWQOS_DOWN_" .. ifn .. " -j IMQ --todev " .. imq_id)

        imq_id = imq_id + 1
    end
end

-- vi: syntax=lua ts=4
