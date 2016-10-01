# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
    config.vm.box = "scotch/box"
    config.vm.network "private_network", ip: "192.168.33.100"
    config.vm.hostname = "blasturbine"
    config.vm.synced_folder "./", "/var/www", :mount_options => ["dmode=777", "fmode=666"]
    config.vm.provision "shell", path: "provision/setup.sh"
    config.vm.provision "shell", inline: <<-SHELL
        /home/vagrant/.rbenv/shims/mailcatcher --http-ip=0.0.0.0
    SHELL
end
