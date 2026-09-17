FROM php:8.2-cli

RUN docker-php-ext-install pdo_mysql

WORKDIR /app
COPY . /app

EXPOSE 8010

# Le serveur intégré de PHP suffit ici : c'est celui utilisé en développement
# (voir README), pas de nginx/Apache à configurer en plus pour ce projet.
CMD ["php", "-S", "0.0.0.0:8010", "-t", "public"]
