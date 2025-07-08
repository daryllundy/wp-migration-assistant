FROM php:8.2-cli
WORKDIR /app
COPY . .
RUN chmod +x wp-migrate
ENTRYPOINT ["./wp-migrate"]
