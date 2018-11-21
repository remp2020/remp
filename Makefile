#! /usr/bin/make

SUB_BEAM="Beam"
SUB_CAMPAIGN="Campaign"
SUB_MAILER="Mailer"
SUB_SSO="Sso"

export TARGET_GOOS TARGET_GOARCH TARGET_SUFFIX

sniff:
	cd $(SUB_BEAM) && make sniff
	cd $(SUB_CAMPAIGN) && make sniff
	cd $(SUB_MAILER) && make sniff
	cd $(SUB_SSO) && make sniff

sniff-fix:
	cd $(SUB_BEAM) && make sniff-fix
	cd $(SUB_CAMPAIGN) && make sniff-fix
	cd $(SUB_MAILER) && make sniff-fix
	cd $(SUB_SSO) && make sniff-fix

lint:
	cd $(SUB_BEAM) && make lint

vet:
	cd $(SUB_BEAM) && make vet

fixcs:
	cd $(SUB_BEAM) && make fixcs
	cd $(SUB_CAMPAIGN) && make fixcs
	cd $(SUB_MAILER) && make fixcs
	cd $(SUB_SSO) && make fixcs

syntax:
	cd $(SUB_BEAM) && make syntax
	cd $(SUB_CAMPAIGN) && make syntax
	cd $(SUB_MAILER) && make syntax
	cd $(SUB_SSO) && make syntax

docker-build:
	cd $(SUB_BEAM) && make docker-build