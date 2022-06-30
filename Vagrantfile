# -*- mode: ruby -*-
# vi: set ft=ruby :

#переменные
LOCAL_HOST_PORT = "8080"                                                                                                                  #создаем переменную для проброса порта
FRIENDLY_VM_NAME = "Wordpress-Vagrant"
#имя виртуальной машины

#stage 0
#настраиваем виртуальную машину
Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/focal64"                                                                                                         #ставим ubuntu 20.04 x64
  config.vm.hostname = "web.local"                                                                                                         #задаем имя виртуальной машины
  config.vm.network "forwarded_port", guest:80, host:LOCAL_HOST_PORT                                                                       #указываем порт для проброса с хостовой на виртуальную
  config.vm.synced_folder "./sync_config_folder", "/sync_config_folder", id: "sync_config_folder", automount: true                         #папка с конфигами для wordpress.
  config.vm.provision "shell", inline: "usermod -a -G vboxsf vagrant"                                                                      #добавляем пользователя vagrant в группу vboxsf для доступа к папке
  config.vm.network "public_network"                                                                                                       #добавляем сетевой интерфейс, который смотрит в локальную сеть
  config.vm.boot_timeout = 1800

  config.vm.provider "virtualbox" do |v|
   v.name = FRIENDLY_VM_NAME                                                                                                               #понятное имя виртуальной машины в virtualbox
   v.memory = 4096                                                                                                                         #изменить кол-во памяти
   v.cpus = 4                                                                                                                             #изменить кол-во cpu
   v.customize ["modifyvm", :id, "--paravirtprovider", "kvm"]                                                                              #тип провайдера виртуализации
                                                                                                                                           #вначале в vagrant'e надо создать пустой dvd'rom и только потом в него можно смонтировать образ
   v.customize ["storageattach", :id, "--storagectl", "IDE", "--port", "1", "--device", "1",
               "--type", "dvddrive", "--mtype", "readonly", "--medium", "emptydrive"]
                                                                                                                                           #смонтируем диск VBoxLinuxAdditions.iso для последующей его установки
   v.customize ["storageattach", :id, "--storagectl", "IDE", "--port", "1", "--device", "1",
               "--type", "dvddrive", "--mtype", "readonly", "--medium", "additions", "--forceunmount"]
  end

#stage 1
#устанавливаем, обновления и чистим систему
 config.vm.provision :shell, inline: <<-SHELL
  echo "Stage 1"
  sudo apt update                                                                                                                           #обновим информацию о пакетах
  sudo apt upgrade -y                                                                                                                       #обновим пакеты

  sudo apt-get install linux-headers-$(uname -r) build-essential dkms -y                                                                    #ставим VBoxLinuxAdditions
  sudo mkdir -p /mnt/cdrom
  sudo mount /dev/cdrom /mnt/cdrom
  cd /mnt/cdrom
  echo y | sudo sh ./VBoxLinuxAdditions.run

  sudo apt autoclean                                                                                                                        #удалить неиспользуемые пакеты из кэша
  sudo apt clean                                                                                                                            #очистка кэша
  sudo apt autoremove                                                                                                                       #удаление ненужных зависимостей
  sudo apt autoremove --purge
  sudo update-grub2                                                                                                                         #обновим загрузчик
 SHELL

 #перезагружаем виртуальную машину
 config.vm.provision :shell do |shell|
  shell.privileged = true
  shell.reboot = true
 end

#stage 2
#линкуем шарную папку, даже после перезагрузки vm
 config.vm.provision :shell, inline: <<-SHELL
  echo "Stage 2"
  sudo rm -rf /sync_config_folder                                                                                                           #линкуем папку для доступа к ней после перезагрузки
  sudo ln -sfT /media/sf_sync_config_folder /sync_config_folder
 SHELL

#stage3
#устанавливаем необходимые пакеты и производим их настройку
#передадим переменную "LOCAL_HOST_PORT" в shell
 config.vm.provision :shell, env: {"LOCAL_HOST_PORT" => LOCAL_HOST_PORT}, inline: <<-SHELL
#ставим пакеты
  echo "Stage 3"
  sudo apt install -y apache2                                                                                                               #ставим apache
  sudo apt install -y libapache2-mod-php                                                                                                    #ставим доп. модули для apache
  sudo apt install -y php-curl php-gd php-mbstring php-xml php-xmlrpc php-soap php-intl php-zip php-sqlite3 php-cli
  sudo apt install -y php-mysql
  sudo apt install -y mysql-server                                                                                                          #ставим базу данных
  sudo a2enmod rewrite                                                                                                                      #включим модуль rewrite для apache

  sudo mkdir /download_content                                                                                                              #создаем папку под скачиваемые файлы
  wget -O /download_content/wordpress_latest.tar.gz https://wordpress.org/latest.tar.gz                                                     #скачиваем wordpress (актуальный релиз)
  tar -xzf /download_content/wordpress_latest.tar.gz -C /download_content                                                                   #распаковываем

#создаем переменные для Wordpress
  myWordpressMysqlUser=wp_user_id_$(tr -cd '[:alnum:]' < /dev/urandom | fold -12 | head -n1)                                                #имя пользователя mysql для wodpress (имя пользователя не должно быть больше 32 символов)
  myWordpressMysqlDbName=wp_db_id_$(tr -cd '[:alnum:]' < /dev/urandom | fold -w30 | head -n1)                                               #имя базы данных mysql для wordpress
  myWordpressMysqlPass=$(tr -cd '[:alnum:]' < /dev/urandom | fold -w30 | head -n1)                                                          #пароль пользователя mysql для wordpress
  myWordpressApache2Pass=$(tr -cd '[:alnum:]' < /dev/urandom | fold -w15 | head -n1)                                                        #пароль пользователя от вэб сервера apache


#создаем базы данных mysql
  mysql -u root -e "CREATE DATABASE $myWordpressMysqlDbName DEFAULT CHARACTER SET utf8;"                                                    #создаем базу данных для wordpress
  mysql -u root -e "create user $myWordpressMysqlUser@'localhost' identified by '$myWordpressMysqlPass';"                                   #создаем пользователя в базе данных для wordpress
  mysql -u root -e "grant all on $myWordpressMysqlDbName.* to $myWordpressMysqlUser@'localhost';"                                           #разрешаем ему подключение с localhost
  mysql -u root -e "flush privileges;"


#переносим папки сайтов
#wordpress
  mv /download_content/wordpress /var/www/wordpress                                                                                         #переместим wordpress в папку хоста
  chown -R root:www-data /var/www/wordpress/                                                                                                #дадим права пользователю www-data на папку wordpress (пользователь www-data - дефолтный пользователь, под которым запущен php)


#почистим за собой
rm -rf /download_content

#настраиваем хостинг
#загружаем пароль для пользователя Wordpress в htpasswd для apache
  echo "$myWordpressApache2Pass" | htpasswd -c -i /etc/apache2/.htpasswd Wordpress
 

  rm /etc/apache2/sites-enabled/000-default.conf                                                                                             #удалим симлинк на дефолтовый конфиг

#установим наш конфиг для apache
  cp /sync_config_folder/apache/001_default.conf /etc/apache2/sites-available/001_default.conf                                               #скопируем подготовленный конфиг для wordpress из общей папки "sync_config_folder/apache"
  chmod -X /etc/apache2/sites-available/001_default.conf                                                                                     #уберем артибут исполняемого файла
  ln -s /etc/apache2/sites-available/001_default.conf /etc/apache2/sites-enabled/001_default.conf                                            #сделаем симлинк для конфигурационного файла, активные конфигурации лежат в папке "sites-enabled"

#подготовим конфигурационный файл для wordpress
  cp /sync_config_folder/wordpress/wp-config.php /var/www/wordpress/wp-config.php                                                            #копируем конфиг wordpress с преднастройками (шаблон)
  chmod -X /var/www/wordpress/wp-config.php                                                                                                  #уберем артибут исполняемого файла
  wget -q -O- https://api.wordpress.org/secret-key/1.1/salt/ | grep 'define' | head >> /var/www/wordpress/wp-config.php                      #получаем ключи и записываем в конфигурационный файл wordpress
  chown -R root:www-data /var/www/wordpress/wp-config.php                                                                                    #дадим права пользователю www-data на конфиг wordpress

#заменим переменные подключения к базе даннах на наши значения для wordpress
  sed -i 's/%example_db_name%/'$myWordpressMysqlDbName'/g' /var/www/wordpress/wp-config.php
  sed -i 's/%example_db_user_name%/'$myWordpressMysqlUser'/g' /var/www/wordpress/wp-config.php
  sed -i 's/%example_db_password%/'$myWordpressMysqlPass'/g' /var/www/wordpress/wp-config.php


#готовим файл с информацией о нашей vm
echo "echo in info file"
echo > /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo __________________________________________________________________________________________ >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo Web sites Wordpress>> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo Available ip address on VM: %ip_address_list% >> /vagrant_up_info.txt
echo Sites available on localhost: http://localhost:$LOCAL_HOST_PORT >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo Wordpress MySQL database name: $myWordpressMysqlDbName >> /vagrant_up_info.txt
echo Wordpress MySQL database user: $myWordpressMysqlUser >> /vagrant_up_info.txt
echo Wordpress MySQL database password: $myWordpressMysqlPass >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo Apache user for Wordpress instants: Wordpress >> /vagrant_up_info.txt
echo Apache user password for Wordpress instants: $myWordpressApache2Pass >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo __________________________________________________________________________________________ >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
echo >> /vagrant_up_info.txt
 SHELL

 #перезагружаем виртуальную машину
  config.vm.provision :shell do |shell|
   shell.privileged = true
   shell.reboot = true
 end

#stage 5
#выводим сообщение после окончания работы скрипта
 config.vm.provision "shell", inline: <<-SHELL
  sed -i 's/%ip_address_list%/'"$(hostname -I)"'/g' /vagrant_up_info.txt
  cat /vagrant_up_info.txt
 SHELL

#stage 6
 config.vm.post_up_message = <<-HEREDOC


  VM info file after config: /vagrant_up_info.txt



 HEREDOC
end