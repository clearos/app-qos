
Name: app-qos
Epoch: 1
Version: 1.4.1
Release: 1%{dist}
Summary: QoS - Core
License: LGPLv3
Group: ClearOS/Libraries
Source: app-qos-%{version}.tar.gz
Buildarch: noarch

%description
The QoS app provides a way to prioritize traffic through your gateway.

%package core
Summary: QoS - Core
Requires: app-base-core
Requires: app-network-core
Requires: app-firewall-core >= 1:1.4.15
Requires: mtr

%description core
The QoS app provides a way to prioritize traffic through your gateway.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/qos
cp -r * %{buildroot}/usr/clearos/apps/qos/

install -d -m 0755 %{buildroot}/var/clearos/qos
install -D -m 0644 packaging/qos.conf %{buildroot}/etc/clearos/qos.conf

%post core
logger -p local6.notice -t installer 'app-qos-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/qos/deploy/install ] && /usr/clearos/apps/qos/deploy/install
fi

[ -x /usr/clearos/apps/qos/deploy/upgrade ] && /usr/clearos/apps/qos/deploy/upgrade

exit 0

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-qos-core - uninstalling'
    [ -x /usr/clearos/apps/qos/deploy/uninstall ] && /usr/clearos/apps/qos/deploy/uninstall
fi

exit 0

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/qos/packaging
%exclude /usr/clearos/apps/qos/tests
%dir /usr/clearos/apps/qos
%dir /var/clearos/qos
/usr/clearos/apps/qos/deploy
/usr/clearos/apps/qos/language
/usr/clearos/apps/qos/libraries
%config(noreplace) /etc/clearos/qos.conf
