sudo su
apt-get update
cd /home/pi
git clone https://github.com/open-homeautomation/miflora.git
cd miflora
wget https://raw.githubusercontent.com/Spoosie/KH_MiFlora/master/RaspberryPI/GetMiFloras.py
apt-get install python3 libglib2.0-dev libbluetooth-dev python3-pip apache2 libboost-python-dev libboost-thread-dev
pip3 install gattlib

Testen mit

python3 GetMiFloras.py

Bei einem Fehler in Form von
	RuntimeError: Set scan parameters failed (are you root?)
einmal 
hciconfig hci0 reset


crontab -e

Neuer Eintrag am Ende
*/10 * * * * python3 /home/pi/miflora/GetMiFloras.py > /var/www/html/plants.log


