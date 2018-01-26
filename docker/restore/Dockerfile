FROM mysql:5.7

RUN apt-get update
RUN apt-get install -y wget \
        curl \
        python-software-properties \
        software-properties-common \
        git-core


WORKDIR /tmp
RUN wget https://repo.percona.com/apt/percona-release_0.1-5.jessie_all.deb
RUN dpkg -i percona-release_0.1-5.jessie_all.deb

RUN apt-get update
RUN apt-get install -y percona-xtrabackup-24

RUN apt-get -y update

RUN apt-get install -y \
        php5 \
        php5-cli \
        php5-mysql \
        php5-curl \
        php5-mcrypt \
        php5-cli \
        php5-dev \
        php5-intl \
        php5-json \
        libapache2-mod-php5 \
        php5-readline

WORKDIR /tmp
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer


COPY restore-entrypoint.sh /usr/local/bin/
RUN ln -sf usr/local/bin/restore-entrypoint.sh /entrypoint.sh

ENTRYPOINT ["restore-entrypoint.sh"]
