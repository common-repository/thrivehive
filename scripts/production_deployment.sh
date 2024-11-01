# This script deploys the current master branch to all production sites
# 1. Zip the contents of the repo into a zip named `changeling.zip`. If you are inside the changeling folder 
#   you can select everything inside and put that into a zip.
# 2. Now you need to initiate an SFTP connection to all of the wordpress servers:
#  - customers.thrivehivesite.com
#  - customers2.thrivehivesite.com
#  - customers3.thrivehivesite.com
#  - customers4.thrivehivesite.com

#  Creds for these can be accessed from AWS EC2.

#  NOTE: Make sure to use the "WHM.pem" file in Google Drive.

# 3. Copy the changeling.zip file into `/root/themes` folder on each of the servers.
# 4. Now that we have all the files on the servers, on each server we can run the script 
#   `/scripts/update_thrivehive_addins.py --changeling`
# 5. These scripts may run for a while, but once they are done, all changeling instances should be up to date!

while true; do
  read -p "Do you wish to deploy the current master branch to production?(y/n)" yn
  case $yn in
    [Yy]* ) 
      cd ~/Thrivehive/warp-prism
      git checkout master
      git pull
      zip -r /tmp/thrivehive.zip . -x "*scripts*" -x "*.git*" -x "*.circleci*" -x "*.vscode*" -x "*tests*" -x "*.DS_Store*"
      for c in customers customers2 customers3 customers4; do
        server="${c}.thrivehivesite.com"
        scp -i ~/Thrivehive/keys/whm.pem /tmp/thrivehive.zip root@$server:/tmp/
        ssh -i ~/Thrivehive/keys/whm.pem root@$server "cp /tmp/thrivehive.zip /root/plugins/"
        ssh -i ~/Thrivehive/keys/whm.pem root@$server "unzip -o /tmp/thrivehive.zip -d /root/cpanel3-skel/public_html/wp-content/plugins/thrivehive/"
        ssh -i ~/Thrivehive/keys/whm.pem root@$server "python ../scripts/update_thrivehive_plugin.py"
      done
      break;;
    [Nn]* ) exit;;
    * ) echo "Please use Y(es) or N(o).";;
  esac
done
echo "Production updated."