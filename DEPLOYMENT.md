# 🚀 Despliegue en Coolify

Este documento contiene las instrucciones para desplegar el proyecto Laravel en **Coolify**.

## Requisitos previos

- Cuenta en Coolify
- Repositorio Git conectado (GitHub, GitLab, Gitea, etc.)
- Variables de entorno configuradas

## Pasos para desplegar en Coolify

### 1. Preparación del proyecto

El proyecto ya contiene:
- ✅ `Dockerfile` - Imagen optimizada para producción
- ✅ `.dockerignore` - Archivos excluidos del build
- ✅ `docker-compose.yml` - Configuración local (opcional)

### 2. Crear una aplicación en Coolify

1. **Login en Coolify** y accede a tu servidor
2. **Crea una nueva aplicación Docker**
3. **Conecta tu repositorio Git**
   - Selecciona el repositorio del proyecto Laravel
   - Elige la rama (ej: `main` o `develop`)

### 3. Configurar variables de entorno

En la sección de **Environment Variables** de Coolify, agrega:

```env
APP_NAME=Laravel API
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_URL=https://tu-dominio.com

FRONTEND_URL=https://tu-frontend.com

# Database
DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=laravel_api
DB_USERNAME=laravel_user
DB_PASSWORD=tu_contraseña_segura

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

# Mail (si necesitas enviar emails)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_contraseña

# Google OAuth (si lo usas)
GOOGLE_CLIENT_ID=tu_google_client_id
GOOGLE_CLIENT_SECRET=tu_google_client_secret
GOOGLE_REDIRECT_URI=https://tu-dominio.com/api/auth/google/callback

# Sanctum
SANCTUM_STATEFUL_DOMAINS=tu-dominio.com,www.tu-dominio.com
```

### 4. Generar la clave de aplicación

Si aún no tienes una clave `APP_KEY`:

```bash
php artisan key:generate
```

O mejor, en tu equipo local:
```bash
docker run --rm -v $(pwd):/app php:8.2-cli bash -c "cd /app && composer install && php artisan key:generate --show"
```

Copia la salida como `APP_KEY` en Coolify.

### 5. Configurar servicios de base de datos

**Opción A: Base de datos administrada (Recomendado)**
- Usa un servidor MySQL externo (ej: AWS RDS, DigitalOcean Managed)
- Configura `DB_HOST` con la dirección del servidor

**Opción B: Base de datos en contenedor**
- Coolify puede proporcionar contenedores adicionales
- Configura un servicio MySQL en Coolify
- Asegúrate de que esté en la misma red

### 6. Configurar almacenamiento persistente

En Coolify, connota los **volúmenes** para:
- `/var/www/html/storage` - Archivos subidos
- `/var/www/html/bootstrap/cache` - Caché de la aplicación

### 7. Desplegar la aplicación

1. **Guarda la configuración** en Coolify
2. **Inicia el deploy** - Coolify construirá la imagen Docker automáticamente
3. **Monitorea los logs** para verificar que todo funciona correctamente

### 8. Ejecutar migraciones después del deploy

Después del primer deploy, necesitas ejecutar las migraciones:

**Opción A: Desde la interfaz de Coolify**
- Ve a **Executions** y crea una nueva ejecución con:
  ```bash
  php artisan migrate --force
  ```

**Opción B: Desde la terminal del contenedor**
```bash
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
```

## Configuración automática de migraciones

Para ejecutar migraciones automáticamente en cada deploy, modifica el `Dockerfile`:

```dockerfile
# Agrega antes de CMD ["apache2-foreground"]
RUN echo '#!/bin/bash\nphp artisan migrate --force\nphp artisan cache:clear\nphp artisan config:cache\nexec apache2-foreground' > /entrypoint.sh \
    && chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
```

## Health Check

El Dockerfile incluye un health check automático. Asegúrate de tener un endpoint de health en tu API:

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
```

## SSL/HTTPS

Coolify gestiona automáticamente los certificados SSL. Solo necesitas configurar el dominio en la aplicación.

## Troubleshooting

### Error: `SQLSTATE[HY000]: General error: 2002 No such file or directory`
- Verifica que `DB_HOST` es correcto (no `localhost`, sino el nombre del servicio)

### Error: `mkdir() failed: Permission denied`
- Revisa los permisos en la carpeta `storage`
- Los volúmenes deben pertenecer al usuario `www-data`

### Memoria insuficiente
- Aumenta la RAM asignada al contenedor en Coolify
- Configura `memory_limit` en PHP

### Logs
- Revisa los logs en **Monitor** o **Logs** en Coolify
- Los logs se guardan en `/var/www/html/storage/logs`

## Comandos útiles

```bash
# Ver logs en tiempo real
docker logs -f nombre-del-contenedor

# Ejecutar comando artisan
docker exec nombre-del-contenedor php artisan tinker

# Reiniciar el contenedor
docker restart nombre-del-contenedor

# Limpiar caché
docker exec nombre-del-contenedor php artisan cache:clear
```

## Optimizaciones adicionales

Para mejorar el rendimiento:

```bash
# En el contenedor:
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Estos comandos pueden ejecutarse como parte del script de entrypoint o manualmente después del deploy.

---

¡Tu aplicación Laravel está lista para producción en Coolify! 🎉
