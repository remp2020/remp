#! /usr/bin/make

SUB_BEAM="Beam"
SUB_CAMPAIGN="Campaign"
SUB_MAILER="Mailer"
SUB_SSO="Sso"

sniff:
	cd $(SUB_BEAM) && make sniff
	cd $(SUB_CAMPAIGN) && make sniff
	cd $(SUB_MAILER) && make sniff
	cd $(SUB_SSO) && make sniff

sniff_fix:
	cd $(SUB_BEAM) && make sniff_fix
	cd $(SUB_CAMPAIGN) && make sniff_fix
	cd $(SUB_MAILER) && make sniff_fix
	cd $(SUB_SSO) && make sniff_fix

fixcs:
	cd $(SUB_BEAM) && make fixcs
	cd $(SUB_CAMPAIGN) && make fixcs
	cd $(SUB_MAILER) && make fixcs
	cd $(SUB_SSO) && make fixcs
