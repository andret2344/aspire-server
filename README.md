# Aspire Server 2

## Development

1. Copy the `.evv` file to `.env.local` and fill its secrets: `APP_SECRET` and `JWT_PASSPHRASE`.

2. Generate keypair.

```shell
openssl genrsa -aes256 -passout pass:"<use JWT_PASSPHRASE here>" -out config/jwt/private.pem 4096
```

```shell
openssl rsa -pubout -in config/jwt/private.pem -passin pass:"<use JWT_PASSPHRASE here>" -out config/jwt/public.pem
```
