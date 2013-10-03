
Name: app-qos
Epoch: 1
Version: 1.5.2
Release: 1%{dist}
Summary: QoS
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base
Requires: app-network

%description
Bandwidth QoS Manager

%package core
Summary: QoS - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-network-core
Requires: app-firewall-core >= 1:1.5.1

%description core
Bandwidth QoS Manager

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/qos
cp -r * %{buildroot}/usr/clearos/apps/qos/

install -d -m 0755 %{buildroot}/var/clearos/qos
install -D -m 0644 packaging/qos.conf %{buildroot}/etc/clearos/qos.conf

%post
logger -p local6.notice -t installer 'app-qos - installing'

%post core
logger -p local6.notice -t installer 'app-qos-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/qos/deploy/install ] && /usr/clearos/apps/qos/deploy/install
fi

[ -x /usr/clearos/apps/qos/deploy/upgrade ] && /usr/clearos/apps/qos/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-qos - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-qos-core - uninstalling'
    [ -x /usr/clearos/apps/qos/deploy/uninstall ] && /usr/clearos/apps/qos/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/qos/controllers
/usr/clearos/apps/qos/htdocs
/usr/clearos/apps/qos/views

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
