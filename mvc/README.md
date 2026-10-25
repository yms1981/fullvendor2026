# fullvendor_test
 
## desarrollo en docker con ssl
Para el entorno de desarrollo se arma imagen que tiene certificado valido ssl 
solicitado por letsencrypt que permite usar **fullvendor-dev.asiv.as** como dominio

Esto se logra usando certbot a mano antes del build con el plugin de cloudflare y teniendo configuradas las credenciales
en /root/.secrets/cloudflare.ini y ejecutando:

```bash
certbot certonly --dns-cloudflare --dns-cloudflare-credentials /root/.secrets/cloudflare.ini -d asiv.as,*.asiv.as --preferred-challenges dns-0
```
luego se copian en el directorio los archivos de certificados y clave
 - /etc/letsencrypt/live/asiv.as/fullchain.pem
 - /etc/letsencrypt/live/asiv.as/privkey.pem

en el dir docker/certificates con nombres 
 - asivas-fullchain.pem
 - asivas-privkey.pem

De esta manera cuando docker compose hace el build de la imagen copia estos archivos en los que están configurados en el sitio de ssl

 - /etc/ssl/certs/ssl-cert-snakeoil.pem
 - /etc/ssl/certs/ssl-cert-snakeoil.key

Y el ssl funciona para fullvendor-app.asiv.as
