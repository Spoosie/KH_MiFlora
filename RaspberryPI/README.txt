sudo su
cd /home/pi
git clone https://github.com/open-homeautomation/miflora.git
cd miflora
wget https://raw.githubusercontent.com/Spoosie/KH_MiFlora/master/RaspberryPI/GetMiFloras.py
apt-get install python3 libglib2.0-dev libbluetooth-dev python3-pip apache2
pip3 install gattlib

Testen mit

python3 GetMiFloras.py


crontab -e

Neuer Eintrag am Ende
*/10 * * * * python3 /home/pi/miflora/GetMiFloras.py > /var/www/html/plants.log


