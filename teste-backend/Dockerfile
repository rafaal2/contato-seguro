FROM php:8.2-cli

# Instala o Composer
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Instala dependências extras
RUN apt-get update && apt-get install -y unzip

# Define o diretório de trabalho
WORKDIR /var/www/html/api

# Copia todos os arquivos do projeto para o container
COPY . .

# Define o script de entrada
CMD ["sh", "-c", "composer install && cd public && php -S 0.0.0.0:8000"]

# Expõe a porta 8000 para acesso externo
EXPOSE 8000
