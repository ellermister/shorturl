FROM kooldev/php:8.2-nginx

WORKDIR /app/public

COPY . /app/public

RUN wget https://raw.githubusercontent.com/ym/chnroutes2/master/chnroutes.txt -O /app/public/chnroutes.txt \
    && chmod -R  775 /app/public && chown -R kool:kool /app/public
