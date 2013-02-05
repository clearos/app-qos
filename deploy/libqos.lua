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

function ParseBandwidthValue(v, buckets)
    local i
    local t = buckets
    local entries = {}
    local cfg
    local ifn
    local value = {}

    if v == nil or string.len(v) == 0 then return t end

    entries = Explode(" ", string.gsub(PackWhitespace(v), "\t", ""))

    for _, cfg in pairs(entries) do
        if cfg ~= nil and string.len(cfg) ~= 0 then
            __, __, ifn, value[0], value[1], value[2],
            value[3], value[4], value[5], value[6] = string.find(
                cfg, "(%w+):(%d+):(%d+):(%d+):(%d+):(%d+):(%d+):(%d+)"
            )

            if ifn == nil or value == nil then
                echo("Invalid bandwidth value syntax detected: " .. cfg)
                return nil
            end

            for i = 0, 6 do
                value[i] = tonumber(value[i])
            end
            t[ifn] = value
        end
    end

    return t
end

function InitializeBandwidthReserved(rate_ifn)
    local i
    local ifn
    local rate
    local limit
    local rate_res = {}

    for ifn, rate in pairs(rate_ifn) do
        rate_res[ifn] = {}
        for i = 0, 6 do
            rate_res[ifn][i] = tonumber(0)
        end
        limit = 100
        while limit > 0 do
            for i = 0, 6 do
                rate_res[ifn][i] = rate_res[ifn][i] + 1
                limit = limit - 1
                if limit == 0 then break end
            end
        end
    end

    return rate_res
end

function InitializeBandwidthLimit(rate_ifn)
    local i
    local ifn
    local rate
    local rate_limit = {}

    for ifn, rate in pairs(rate_ifn) do
        rate_limit[ifn] = {}
        for i = 0, 6 do
            rate_limit[ifn][i] = 100
        end
    end

    return rate_limit
end

function ValidateBandwidthReserved(name, rate_res)
    local i
    local r
    local ifn
    local rate
    local limit

    for ifn, r in pairs(rate_res) do
        limit = 100
        for _, rate in pairs(r) do
            limit = limit - rate
            if limit < 0 then
                error(name .. " bandwidth overflow (>100%) for interface: " .. ifn)
            end
        end
        if limit > 0 then
            debug(string.format("%s bandwidth underflow, %d%%%% unassigned for interface: %s", name, limit, ifn))
        end
        while limit > 0 do
            for i = 0, 6 do
                rate_res[ifn][i] = rate_res[ifn][i] + 1
                limit = limit - 1
                debug(string.format("%s: adding 1%%%% to bucket #%d for interface: %s", name, i, ifn))
                if limit == 0 then break end
            end
        end
    end
end

function ValidateBandwidthLimit(name, rate_res, rate_limit)
    local i
    local r
    local ifn
    local limit

    for ifn, r in pairs(rate_limit) do
        for i, limit in pairs(r) do
            if limit < rate_res[ifn][i] then
                error(string.format("%s bandwidth underflow (<%d%%, bucket #%d) for interface: %s", name, rate_res[ifn][i], i, ifn))
            end
            if limit > 100 then
                error(string.format("%s bandwidth overflow (>%d%%, bucket #%d) for interface: %s", name, 100, i, ifn))
            end
        end
    end
end

function QosExecute(direction, rate_ifn, rate_res, rate_limit, priomark)
    local id = 0
    local rule
    local rate
    local limit
    local param
    local ifn_name
    local chain_qos

    if direction == 1 then
        execute(string.format("%s %s numdevs=%d",
            MODPROBE, "imq",
            TableCount(WANIF_CONFIG)))
    end

    for _, ifn in pairs(WANIF_CONFIG) do
        if direction == 0 then
            ifn_name = ifn
            chain_qos = "BWQOS_UP_" .. ifn
            execute(IPBIN .. " link set dev " .. ifn .. " qlen 30")
        else
            ifn_name = "imq" .. id
            chain_qos = "BWQOS_DOWN_" .. ifn
            execute(IPBIN .. " link set " .. ifn_name .. " up")
        end

        execute(TCBIN .. " qdisc add dev " .. ifn_name ..
            " root handle 1: htb default " .. (id + 1) .. "6")
        execute(TCBIN .. " class add dev " .. ifn_name ..
            " parent 1: classid 1:1 htb rate " ..
            rate_ifn[ifn] .. "kbit")

        for i = 0, 6 do
            rate = rate_res[ifn][i] * rate_ifn[ifn] / 100
            limit = rate_limit[ifn][i] * rate_ifn[ifn] / 100

            execute(TCBIN .. " class add dev " .. ifn_name ..
                " parent 1:1 classid 1:" ..
                (id + 1) .. i .. " htb rate " ..
                rate .. "kbit ceil " .. limit .. "kbit prio " .. i)
            execute(TCBIN .. " qdisc add dev " .. ifn_name ..
                " parent 1:" .. (id + 1) .. i ..
                " handle " .. (id + 1) .. i ..
                ": sfq perturb 10")
            execute(TCBIN .. " filter add dev " .. ifn_name ..
                " parent 1:0 prio 0 protocol ip handle " ..
                (id + 1) .. i ..
                " fw flowid 1:" .. (id + 1) .. i)
        end

        -- Create QoS chain
        iptc_create_chain("mangle", chain_qos)

        -- Add configured MARK rules here
        for _, rule in ipairs(priomark.ipv4) do
            param = " "
            if tonumber(rule.type) == direction then
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

                iptables("mangle", "-A " .. chain_qos .. param ..
                    "-j MARK --set-mark " .. (id + 1) .. rule.prio)
            end
        end

        for _, rule in ipairs(priomark.custom) do
            param = " "
            if tonumber(rule.type) == direction then
                iptables("mangle", "-A " .. chain_qos .. 
                    " " .. rule.param ..
                    " -j MARK --set-mark " .. (id + 1) .. rule.prio)
            end
        end

        if direction == 0 then
            iptables("mangle",
                "-I POSTROUTING -o " .. ifn .. " -j " .. chain_qos)
        else
            iptables("mangle",
                "-A " .. chain_qos .. " -j IMQ --todev " .. id)
            iptables("mangle",
                "-I PREROUTING -i " .. ifn .. " -j " .. chain_qos)
        end

        id = id + 1
    end
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
    local rate_up = {}
    local rate_up_res = {}
    local rate_up_limit = {}
    local rate_down = {}
    local rate_down_res = {}
    local rate_down_limit = {}
    local rule
    local rules
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
    end

    rate_up = ParseBandwidthVariable(QOS_UPSTREAM)
    rate_down = ParseBandwidthVariable(QOS_DOWNSTREAM)

    rate_up_res = InitializeBandwidthReserved(rate_up)
    rate_up_limit = InitializeBandwidthLimit(rate_up)
    rate_down_res = InitializeBandwidthReserved(rate_down)
    rate_down_limit = InitializeBandwidthLimit(rate_down)

    rate_up_res = ParseBandwidthValue(QOS_UPSTREAM_BWRES, rate_up_res)
    rate_up_limit = ParseBandwidthValue(QOS_UPSTREAM_BWLIMIT, rate_up_limit)
    rate_down_res = ParseBandwidthValue(QOS_DOWNSTREAM_BWRES, rate_down_res)
    rate_down_limit = ParseBandwidthValue(QOS_DOWNSTREAM_BWLIMIT, rate_down_limit)
    
    ValidateBandwidthReserved("Reserved upstream", rate_up_res)
    ValidateBandwidthReserved("Reserved downstream", rate_down_res)
    ValidateBandwidthLimit("Upstream limit", rate_up_res, rate_up_limit)
    ValidateBandwidthLimit("Downstream limit", rate_down_res, rate_down_limit)

    QosExecute(0, rate_up, rate_up_res, rate_up_limit, priomark)
    QosExecute(1, rate_down, rate_down_res, rate_down_limit, priomark)
end

-- vi: syntax=lua ts=4
