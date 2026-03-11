# 🐳 Docker Setup para Coolify

Este proyecto incluye una configuración completa de Docker para desplegar en **Coolify** de forma rápida y segura.

## 📦 Archivos Docker incluidos

- **Dockerfile** - Configuración multi-stage optimizada para producción
- **.dockerignore** - Excluye archivos innecesarios del build
- **docker-compose.yml** - Configuración para desarrollo local
- **entrypoint.sh** - Script de inicialización que ejecuta migraciones
- **DEPLOYMENT.md** - Instrucciones detalladas para Coolify

## 🚀 Despliegue rápido en Coolify

1. **Conecta tu repositorio** a Coolify
2. **Crea una aplicación Docker**
3. **Configura las variables de entorno** (ver `DEPLOYMENT.md`)
4. **Despliega** - Coolify construirá y ejecutará automáticamente

Para instrucciones detalladas, ver [DEPLOYMENT.md](./DEPLOYMENT.md)

## 💻 Desarrollo local con Docker

### Requisitos

- Docker Desktop instalado
- Docker Compose

### Levantar el proyecto localmente

```bash
# Copiar .env si no existe
cp .env.example .env

# Construir y levantar los servicios
docker-compose up -d

# Ejecutar migraciones
docker-compose exec laravel php artisan migrate --seed

# Ver logs
docker-compose logs -f laravel
```

### Acceder a la aplicación

- **API**: http://localhost
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

### Comandos útiles

```bash
# Ejecutar comando artisan
docker-compose exec laravel php artisan tinker

# Ver logs
docker-compose logs -f laravel

# Detener todos los servicios
docker-compose down

# Remover volúmenes (cuidado: elimina la BD)
docker-compose down -v
```

## 🔧 Personalizar el Dockerfile

Si necesitas:

### Agregar extensiones PHP

Edita el Dockerfile en la sección `RUN docker-php-ext-install`:

```dockerfile
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    redis  # <-- agregar aquí
```

### Cambiar el servidor web

Por defecto usa Apache. Para usar Nginx, reemplaza la imagen base.

### Instalar paquetes del sistema

Agrega en la sección de `apt-get install`:

```dockerfile
RUN apt-get update && apt-get install -y \
    git \
    curl \
    # ... otros paquetes ...
    imagemagick \  # <-- agregar aquí
    && rm -rf /var/lib/apt/lists/*
```

## 📋 Variables de entorno requeridas

Las siguientes variables deben configurarse en Coolify:

```env
APP_NAME=Laravel API
APP_ENV=production
APP_KEY=base64:xxxxx
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=laravel_api
DB_USERNAME=root
DB_PASSWORD=xxxxxx
FRONTEND_URL=https://tu-dominio.com
```

Ver [DEPLOYMENT.md](./DEPLOYMENT.md#3-configurar-variables-de-entorno) para la lista completa.

## 🔐 Seguridad

- El Dockerfile usa **multi-stage build** para reducir el tamaño de la imagen
- Las dependencias de desarrollo se excluyen en producción
- Los volúmenes están limitados a directorios específicos
- Las migraciones se ejecutan automáticamente al iniciar

## 📊 Monitoreo

El Dockerfile incluye:
- **Health checks** automáticos
- **Logs** configurados con Docker
- **Permisos correctos** para el servidor web

Ver logs en Coolify: **Monitor** → **Logs**

## 🆘 Problemas comunes

### La imagen no se construye

Verifica:
- Tengas todas las variables requeridas en `.env`
- El repositorio esté bien conectado a Coolify

### La aplicación no inicia

Revisa los logs:
- En Coolify: **Monitor** → **Logs**
- Localmente: `docker-compose logs -f laravel`

### Problemas de permisos

Asegúrate de que los volúmenes tienen permisos correctos para `www-data`:

```bash
docker-compose exec laravel chmod -R 755 storage bootstrap/cache
```

## 📚 Recursos

- [Documentación de Coolify](https://coolify.io/docs)
- [Docker Compose Docs](https://docs.docker.com/compose/)
- [Laravel Deployment](https://laravel.com/docs/11/deployment)

---

**¡Listo! Tu proyecto está configurado para producción en Coolify.**
