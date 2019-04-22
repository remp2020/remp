FROM spotify/kafka

# spotify/kafka uses Debian Jessie as base system, apts which were removed from the mirror network and partially moved to archive
# https://lists.debian.org/debian-devel-announce/2019/03/msg00006.html

# validity of repositories is older than what system allows, disabling the check
RUN echo "Acquire::Check-Valid-Until \"false\";" | tee /etc/apt/apt.conf

# jessie-updates was not moved to the archive yet
RUN sed -i /jessie-updates/d /etc/apt/sources.list

# replace main repository with archive repository
RUN sed -i s/deb\.debian\.org/archive.debian.org/g /etc/apt/sources.list
RUN sed -i s/deb\.debian\.org/archive.debian.org/g /etc/apt/sources.list.d/jessie-backports.list

RUN apt-get update

RUN apt-get -y install net-tools

ADD create-topics.sh .

RUN chmod +x create-topics.sh

ADD kafka-start.sh .

RUN chmod +x kafka-start.sh

CMD ["./kafka-start.sh"]
