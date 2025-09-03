FROM nginx:stable
RUN rm -f /etc/nginx/conf.d/default.conf
COPY conf.d/default.conf /etc/nginx/conf.d/default.conf
WORKDIR /var/www/html

