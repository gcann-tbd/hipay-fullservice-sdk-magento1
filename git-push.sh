defaultPush='n'
defaultMessage=$(git log | head -5 | grep "    ")
git add .
git status
if [ "$defaultMessage" != "" ]; then
	read -p "Please, write your commit message. Default [$(echo $defaultMessage)] " message
	message=${message:-$defaultMessage}
	if [ "$message" = "$defaultMessage" ]; then
		git commit -am "$(echo $message)"
	else
		git commit -am "$message"
	fi
else
	while [ "$message" = "" ]; do
		read -r -p "Please, write your commit message. Default [$(echo $defaultMessage)] " message
	done
	git commit -am "$message"
fi
read -p "Are you sure to push your commit ? Default : [$defaultPush] " push
push=${push:-$defaultPush}
if [ "$push" = "y" ]; then
	git push
fi