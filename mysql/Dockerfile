FROM mysql:5.7
COPY ./initdb.d/my.cnf /etc/mysql/conf.d
RUN apt-get update && \
    apt-get install -y vim