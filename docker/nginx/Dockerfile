ARG VERSION

FROM nginx:${VERSION}-alpine as dev

COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf

FROM dev as prod

COPY ./assets /app/public