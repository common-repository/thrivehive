while true; do
  read -p "Do you wish to update the skeleton directory for QA with your current branch?(y/n)" yn
  case $yn in
    [Yy]* ) 
      cd ~/Thrivehive/warp-prism
      git pull
      zip -r /tmp/thrivehive.zip . -x "*scripts*" -x "*.git*" -x "*.circleci*" -x "*.vscode*" -x "*tests*" -x "*.DS_Store*"
      for server in alice.metallicocean.com; do
        scp -i ~/Thrivehive/keys/whm.pem /tmp/thrivehive.zip root@$server:/tmp/
        ssh -i ~/Thrivehive/keys/whm.pem root@$server "cp /tmp/thrivehive.zip /root/plugins/"
        ssh -i ~/Thrivehive/keys/whm.pem root@$server "unzip -o /tmp/thrivehive.zip -d /root/cpanel3-skel/public_html/wp-content/plugins/thrivehive/"
      done
      break;;
    [Nn]* ) exit;;
    * ) echo "Please use Y(es) or N(o).";;
  esac
done
echo "Skeleton directory updated."