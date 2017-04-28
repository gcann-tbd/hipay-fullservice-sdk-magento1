green='\033[0;32m'
yellow='\033[0;33m'
red='\033[0;31m'
blue='\033[0;36m'
noColor='\033[0m'
header="bin/tests/"

failfastDefault='n'
relance='y'
autreDefault='y'
autre='y'
cacheDefault='n'
debugDefault='n'
partieDefault='1'
cardTypeDefault='VISA'
setTypeCard=""

setBackendCredentials() {
	printf "\n"
	if [ "$LOGIN_BACKEND" = "" ] || [ "$PASS_BACKEND" = "" ]; then
	    while [ "$LOGIN_BACKEND" = "" ]; do
	        read -p "LOGIN_BACKEND variable is empty. Insert your BO TPP login here : " login
	        LOGIN_BACKEND=$login
	    done
	    while [ "$PASS_BACKEND" = "" ]; do
	        read -p "PASS_BACKEND variable is empty. Insert your BO TPP password here : " pass
	        PASS_BACKEND=$pass
	    done
	fi
}
affectTypeCC() {
	case $1 in
		1)
			setTypeCard="VISA";;
		2)
			setTypeCard="MasterCard";;
		3)
			setTypeCard="CB";;
		*)
			setTypeCard="0";;
	esac
}
invalidCommand() {
	printf "${red}Commande invalide ! Veuillez réessayez...${noColor}"
	sleep 2
	bash $header'prototype.sh'
}
adminTests() {
	affectTypeCC $1
	if [ "$setTypeCard" != "0" ]; then
		casperjs test $pathPreFile $header$2/0*/[0-9][0-9][0-9][0-9]-*.js --url=$BASE_URL --type-cc=$setTypeCard --url-mailcatcher=$URL_MAILCATCHER --ignore-ssl-errors=true --ssl-protocol=any
	else
		invalidCommand
	fi
}
frontendTests() {
	if [[ "$folder" == *"001"* ]]; then
		setBackendCredentials
	fi
	affectTypeCC $1
	if [ "$setTypeCard" != "0" ]; then
		casperjs test $pathPreFile $header$2/$3*/[0-9][0-9][0-9][0-9]-*.js --url=$BASE_URL --type-cc=$setTypeCard --url-mailcatcher=$URL_MAILCATCHER --login-backend=$LOGIN_BACKEND --pass-backend=$PASS_BACKEND --ignore-ssl-errors=true --ssl-protocol=any
	else
		invalidCommand
	fi
}

clear

printf "${green}************************************************************
******************* Magento1 + CasperJS *******************
************************************************************${noColor}\n"
printf "\n${yellow}MENU${noColor}"
printf "\n${green}1${noColor}: Teste toutes les méthodes de paiement sur chaque environnement\n${green}2${noColor}: Teste une méthode de paiement sur un ou plusieurs environnements
${green}3${noColor}: Teste un fichier d'une méthode de paiement et d'un environnement\n${green}4${noColor}: Teste un ou plusieurs fichiers de différentes méthodes de paiement
${green}5${noColor}: Teste un fichier d'une méthode de paiement en mode DEBUG\n${green}CTRL+C${noColor} pour sortir\n\n"

menuDefault='3'
read -p "Saisissez le numéro correspondant à votre demande. Default: [$(printf $yellow)Teste un fichier précis$(printf $noColor)] : " menu
menu=${menu:-$menuDefault}

BASE_URL="http://localhost:8095/"
URL_MAILCATCHER="http://localhost:1095/"
pathPreFile=${header}000*/000[0-1]*.js
pathDir=${header}0[0-1][0-9]*
pathFile=${pathDir}/[0-1]*/[0-9][0-9][0-9][0-9]-*.js

case $menu in
	1)
		setBackendCredentials

		printf "\n"
		read -p "Effacer le cache pour les tests (y/n) Default: [$(printf $yellow)${cacheDefault}$(printf $noColor)] : " cache
		cache=${cache:-$cacheDefault}

		printf "\n"
		read -p "Activer le fail-fast (y/n) Default: [$(printf $yellow)${failfastDefault}$(printf $noColor)] : " failfast
		failfast=${failfast:-$failfastDefault}

		clear

		if [ "$cache" = "y" ]; then
			if [ "$(ls -A ~/.local/share/Ofi\ Labs/PhantomJS/)" ]; then
			    rm -rf ~/.local/share/Ofi\ Labs/PhantomJS/*
			    printf "Cache cleared !\n\n"
			else
			    printf "Pas de cache à effacer !\n\n"
			fi
		fi

		if [ "$failfast" = "y" ]; then
			printf "${yellow}Exécution des tests CasperJS avec fail-fast${noColor}\n\n"
			casperjs test $pathPreFile $pathFile --url=$BASE_URL --url-mailcatcher=$URL_MAILCATCHER --login-backend=$LOGIN_BACKEND --pass-backend=$PASS_BACKEND --fail-fast --xunit=${header}result.xml --ignore-ssl-errors=true --ssl-protocol=any
		else
			printf "${yellow}Exécution des tests CasperJS sans fail-fast${noColor}\n\n"
			casperjs test $pathPreFile $pathFile --url=$BASE_URL --url-mailcatcher=$URL_MAILCATCHER --login-backend=$LOGIN_BACKEND --pass-backend=$PASS_BACKEND --xunit=${header}result.xml --ignore-ssl-errors=true --ssl-protocol=any
		fi;;
	2)
		for d in $pathDir; do
			if [ "$d" != "${header}000_parameters" ]; then
				tabDir+=($d)
			fi
		done

		if [ "${tabDir[0]}" = "$pathDir" ]; then
			unset tabDir[0]
		fi

		for d in ${tabDir[@]}; do
			for f in $d/[0-1]*/[0-9][0-9][0-9][0-9]-*.js; do
				if [ "$f" = "$d/[0-1]*/[0-9][0-9][0-9][0-9]-*.js" ]; then
					emptyFolder+=($d)
				fi
			done
		done

		for d in ${emptyFolder[@]}; do
			tabDir=("${tabDir[@]/$d}")
		done

		if [ ${#tabDir[*]} -ne 0 ]; then
			clear

			printf "${yellow}Liste des dossiers${noColor}\n\n"
			numero=0
			for d in ${tabDir[@]}; do
				array[numero]=$(echo $d | cut -d '/' -f3)
				numero=$((numero+1))
				if [ $((numero%2)) -eq 0 ]; then
					printf "${green}$numero${noColor}: ${blue}${array[$((numero-1))]}${noColor}\n"
				else
					printf "${green}$numero${noColor}: ${array[$((numero-1))]}\n"
				fi
			done

			dossierDefault=$numero
			printf "\n"
			read -p "Saisissez le numéro correspondant au dossier que vous voulez tester. Default: [$(printf $yellow)${array[$((numero-1))]}$(printf $noColor)] : " dossier
			dossier=${dossier:-$dossierDefault}

			if [ $dossier -ge 1 ] && [ $dossier -le $(echo ${#array[*]}) ]; then
				folder=${array[$((dossier-1))]}

				printf "\n"
				printf "${yellow}${folder}${noColor}\n\n"

				if [ $(find $header$folder/[0-1]* -maxdepth 0 -type d | wc -l) -eq 2 ]; then
					chaine="${green}1${noColor}: Tester les parties admin et frontend\n${green}2${noColor}: Tester la partie admin\n${green}3${noColor}: Tester la partie frontend\n"
					for subD in $header$folder/[0-1]*; do
						if find "$subD" -maxdepth 0 -empty | read; then
							case $subD in
								*"admin"*)
									chaine="${green}1${noColor}: Tester la partie frontend\n";;
								*"frontend"*)
									chaine="${green}1${noColor}: Tester la partie admin\n";;
							esac
						fi
					done
				else
					for subD in $header$folder/[0-1]*; do
						case $subD in
							*"admin"*)
								chaine="${green}1${noColor}: Tester la partie admin\n";;
							*"frontend"*)
								chaine="${green}1${noColor}: Tester la partie frontend\n";;
						esac
					done
				fi

				printf "$chaine\n"
				
				read -p "Indiquez votre choix en tapant le numéro correspondant. Default: [$(printf $yellow)${partieDefault}$(printf $noColor)] : " partie
				partie=${partie:-$partieDefault}

				printf "\n${green}1${noColor}: VISA\n${green}2${noColor}: MasterCard\n${green}3${noColor}: CB\n\n"
				read -p "Choisissez votre type de carte de crédit : Default [$(printf $yellow)${cardTypeDefault}$(printf $noColor)] : " cardType
				cardTypeDefault='1'
				cardType=${cardType:-$cardTypeDefault}

				if [[ "$chaine" == *"2"* ]]; then
					case $partie in
						1)
							frontendTests $cardType $folder;;
						2)
							adminTests $cardType $folder;;
						3)
							frontendTests $cardType $folder 1;;
						*)
							invalidCommand;;
					esac
				elif [[ "$chaine" == *"partie admin"* ]]; then
					case $partie in
						1)
							adminTests $cardType $folder;;
						*)
							invalidCommand;;
					esac
				else
					case $partie in
						1)
							frontendTests $cardType $folder 1;;
						*)
							invalidCommand;;
					esac
				fi
			else
				invalidCommand
			fi
		else
			printf "${yellow}Aucun dossier de tests !${noColor}\n"
			printf "${red}Redirection dans le menu principal...${noColor}"
			sleep 2
			bash $header'prototype.sh'
		fi;;
	3)
		for t in $pathFile; do
			tabFile+=($t)
		done

		if [[ "${tabFile[0]}" == "$pathFile" ]]; then
			unset tabFile[0]
		fi

		if [ ${#tabFile[*]} -ne 0 ]; then
			while [[ "$autre" == "y" ]]; do
				relance='y'

				clear

				printf "${yellow}Liste des fichiers Casper${noColor}\n\n"
				numero=0
				for f in ${tabFile[@]}; do
					array[numero]=$(echo $f | cut -d '/' -f3,4,5)
					numero=$((numero+1))
					if [ $((numero%2)) -eq 0 ]; then
						printf "${green}$numero${noColor}: ${blue}$(echo $f | cut -d '/' -f5)${noColor}\n"
					else
						printf "${green}$numero${noColor}: $(echo $f | cut -d '/' -f5)\n"
					fi
				done

				fichierDefault=$numero
				printf "\n"
				read -p "Saisissez le numéro correspondant au fichier Casper que vous voulez tester. Default: [$(printf $yellow)$(echo ${array[$((fichierDefault-1))]} | cut -d '/' -f3)$(printf $noColor)] : " fichier
				fichier=${fichier:-$fichierDefault}

				if [ $fichier -ge 1 ] && [ $fichier -le $(echo ${#array[*]}) ]; then
					file=${array[$((fichier-1))]}

					printf "\n"
					printf "${yellow}${file}${noColor}\n"

					printf "\n${green}1${noColor}: VISA\n${green}2${noColor}: MasterCard\n${green}3${noColor}: CB\n\n"
					read -p "Choisissez votre type de carte de crédit : Default [$(printf $yellow)${cardTypeDefault}$(printf $noColor)] : " cardType
					cardTypeDefault='1'
					cardType=${cardType:-$cardTypeDefault}
					
					affectTypeCC $cardType

					while [[ "$relance" == "y" ]]; do
						if [[ "$file" == *"1"*"/0101"* ]]; then
							setBackendCredentials

							printf "\n"
							while [ "$order" = "" ]; do
						        read -p "In order to simulate notification to Magento server, put here an order ID : " order
						    done
						fi

						if [ "$setTypeCard" != "0" ]; then
							casperjs test $pathPreFile $header$file --url=$BASE_URL --type-cc=$setTypeCard --url-mailcatcher=$URL_MAILCATCHER --login-backend=$LOGIN_BACKEND --pass-backend=$PASS_BACKEND --ignore-ssl-errors=true --ssl-protocol=any --order=$order
						else
							invalidCommand
						fi

						relanceDefault='y'
						printf "\n"
						read -p "Relancer le test. Default : [$(printf $yellow)${relanceDefault}$(printf $noColor)] : " relance
						relance=${relance:-$relanceDefault}
					done

					printf "\n"
					read -p "Tester un autre fichier. Default : [$(printf $yellow)${autreDefault}$(printf $noColor)] : " autre
					autre=${autre:-$autreDefault}
				else
					printf "${red}Commande invalide ! Veuillez réessayez...${noColor}"
					sleep 2
					bash $header'prototype.sh'
					break
				fi
				cardTypeDefault='VISA'
			done
		else
			printf "${yellow}Aucun fichier de tests !${noColor}\n"
			printf "${red}Redirection dans le menu principal...${noColor}"
			sleep 2
			bash $header'prototype.sh'
		fi;;
	4)
		while [[ "$autre" == "y" ]]; do
			relance='y'
			# création et réinitialisation du talbeau
			tabFile=()
			file=()

			for t in $pathFile; do
				if [[ "$t" != "$header$param" ]] && [[ "$t" != "$header$func" ]]; then
					tabFile+=($t)
				fi
			done

			if [[ "${tabFile[0]}" == "$pathFile" ]]; then
				unset tabFile[0]
			fi
			
			if [ ${#tabFile[*]} -ne 0 ]; then

				clear

				printf "${yellow}Liste des fichiers de tests Casper${noColor}\n\n"
				numero=0
				for f in ${tabFile[@]}; do
					array[numero]=$(echo $f | cut -d '/' -f3,4,5)
					numero=$((numero+1))
					if [ $((numero%2)) -eq 0 ]; then
						printf "${green}$numero${noColor}: ${blue}$(echo $f | cut -d '/' -f5)${noColor}\n"
					else
						printf "${green}$numero${noColor}: $(echo $f | cut -d '/' -f5)\n"
					fi
				done

				# index tableau des fichiers séléctionnées par l'utilisateur
				i=0
				# initialisation de la variable $fichier à 1 pour passer dans la boucle until
				fichier=1

				# jusqu'à ce que l'utilisateur est tapé la valeur 0
				until [ $fichier -eq 0 ]; do

					fichierDefault=$numero
					printf "\n"
					read -p "Saisissez le numéro correspondant au fichier Casper que vous voulez tester. Taper $(printf $yellow)0$(printf $noColor) pour terminer votre séléction. Default: [$(printf $yellow)$(echo ${array[$((fichierDefault-1))]} | cut -d '/' -f3)$(printf $noColor)] : " fichier
					fichier=${fichier:-$fichierDefault}

					if [ $fichier -ge 0 ] && [ $fichier -le $((${#tabFile[@]}-${#file[@]})) ]; then
						# Si l'utilisateur tape une valeur autre que 0
						if [ $fichier -ne 0 ]; then

							# On supprime du talbeau qui affiche la liste des fichiers tests le(s) fichier(s) que l'utilisateur a déjà séléctionné
							file[$i]=${array[$((fichier-1))]}
							for f in ${file[@]}; do
								f="$header$f"
								tabFile=("${tabFile[@]/$f}")
							done

							clear

							# On affiche alors la liste des fichiers mise à jour (sans les fichiers déjà séléctionnés)
							printf "${yellow}Liste des fichiers de tests Casper${noColor}\n\n"
							numero=0
							for f in ${tabFile[@]}; do
								array[numero]=$(echo $f | cut -d '/' -f3,4,5)
								numero=$((numero+1))
								if [ $((numero%2)) -eq 0 ]; then
									printf "${green}$numero${noColor}: ${blue}$(echo $f | cut -d '/' -f5)${noColor}\n"
								else
									printf "${green}$numero${noColor}: $(echo $f | cut -d '/' -f5)\n"
								fi
							done
							# Incrémente l'index du tableau des fichiers séléctionnés pour un possible prochain fichier test séléctionné par l'utilisateur
							i=$((i+1))
						# Si la valeur tapée par l'utilisateur est égale à 0
						else

							# On affiche le(s) fichier(s) séléctionné(s) ou un message s'il n'y a aucun fichier séléctionné
							printf "\n"
							if [ ${#file[@]} -ne 0 ]; then
								printf "${yellow}%s${noColor}\n" "${file[@]}"
							else
								printf "${yellow}Aucun fichier séléctionné !\n${noColor}"
							fi

							# Ajoute la chaine "tests/" dans chaque fichier (chaine) séléctionné pour compiler les fichiers test
							for ((f=0; f<${#file[@]}; f++)); do
								file[$f]="$header${file[$f]}"
							done

							printf "\n${green}1${noColor}: VISA\n${green}2${noColor}: MasterCard\n${green}3${noColor}: CB\n\n"
							read -p "Choisissez votre type de carte de crédit : Default [$(printf $yellow)${cardTypeDefault}$(printf $noColor)] : " cardType
							cardTypeDefault='1'
							cardType=${cardType:-$cardTypeDefault}

							affectTypeCC $cardType

							while [[ "$relance" == "y" ]]; do
								if [[ "${file[@]}" == *"1"*"/0101"* ]]; then
									setBackendCredentials

									printf "\n"
									while [ "$order" = "" ]; do
								        read -p "In order to simulate notification to Magento server, put here an order ID : " order
								    done
								fi

								if [ "$setTypeCard" != "0" ]; then
									casperjs test $pathPreFile ${file[@]} --url=$BASE_URL --type-cc=$setTypeCard --url-mailcatcher=$URL_MAILCATCHER --login-backend=$LOGIN_BACKEND --pass-backend=$PASS_BACKEND --ignore-ssl-errors=true --ssl-protocol=any --order=$order
								else
									invalidCommand
								fi

								relanceDefault='y'
								printf "\n"
								read -p "Relancer le test. Default : [$(printf $yellow)${relanceDefault}$(printf $noColor)] : " relance
								relance=${relance:-$relanceDefault}
							done

							printf "\n"
							read -p "Tester d'autres fichiers. Default : [$(printf $yellow)${autreDefault}$(printf $noColor)] : " autre
							autre=${autre:-$autreDefault}
						fi
					else
						invalidCommand
					fi
					cardTypeDefault='VISA'
				done
			else
				invalidCommand
			fi
		done;;
	5)
		echo "wait";;
	*)
		invalidCommand;;
esac