# Productos API

API RESTful para la gestion de productos y precios en multiples divisas, construida con Laravel 12, PHP 8.5, MySQL 8 y Docker.

## Tecnologias

- **Laravel 12.53.0** - Framework PHP
- **PHP 8.5.3** - Con OPcache y JIT habilitados
- **Eloquent ORM** - ORM integrado de Laravel para el manejo de base de datos
- **MySQL 8.0** - Base de datos relacional
- **Docker Compose** - Contenedores (app, nginx, mysql, node)
- **Laravel Sanctum** - Autenticacion por Bearer Token
- **Spatie Laravel Permission** - Roles y permisos

## Requisitos Previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y corriendo
- [Git](https://git-scm.com/)

## Instalacion y Configuracion

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd productos-api-prueba
```

### 2. Configurar variables de entorno

Copiar el archivo de ejemplo y ajustar las credenciales si es necesario:

```bash
cp .env.example .env
```

Las variables importantes en `.env`:

| Variable | Descripcion | Valor por defecto |
|---|---|---|
| `DB_HOST` | Host de la base de datos | `db` |
| `DB_DATABASE` | Nombre de la base de datos | `products` |
| `DB_USERNAME` | Usuario de MySQL | `laravel` |
| `DB_PASSWORD` | Contrasena de MySQL | `products_karateka_programador` |
| `DB_ROOT_PASSWORD` | Contrasena root de MySQL | *(ver .env.example)* |

### 3. Levantar los contenedores Docker

```bash
docker compose up -d --build
```

Esto creara y levantara los siguientes servicios:

| Servicio | Contenedor | Puerto |
|---|---|---|
| **app** | `laravel_app` | PHP-FPM (interno) |
| **web** | `laravel_web` | `8080` (nginx) |
| **db** | `laravel_db` | `3306` (mysql) |
| **node** | `laravel_node` | `5173` (vite) |

### 4. Instalar dependencias de PHP

```bash
docker compose exec app composer install
```

### 5. Generar la clave de la aplicacion

```bash
docker compose exec app php artisan key:generate
```

### 6. Ejecutar las migraciones

Esto crea todas las tablas necesarias (users, products, divisas, products_prices, permisos, tokens):

```bash
docker compose exec app php artisan migrate
```

### 7. Ejecutar los seeders

Esto crea los datos iniciales: usuarios, roles, permisos, divisas, productos y precios:

```bash
docker compose exec app php artisan db:seed
```

Los seeders ejecutan en orden:
1. **UsersSeeder** - Crea permisos, roles (admin, editor, viewer) y 3 usuarios de prueba
2. **DivisasSeeder** - Crea las divisas (Dolar, Euro, Peso Colombiano)
3. **ProductSeeder** - Crea 5 productos de ejemplo
4. **ProductsPriceSeeder** - Genera precios por producto en todas las divisas

### 8. (Opcional) Optimizar para rendimiento

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan event:cache
docker compose exec app composer dump-autoload --optimize
```

> **Nota:** Si modificas codigo despues de cachear, necesitas limpiar los caches:
> ```bash
> docker compose exec app php artisan optimize:clear
> ```

## La API estara disponible en: `http://localhost:8080/api`

---

## Swagger / OpenAPI

Con la aplicación iniciada, abre **http://localhost:8080/api/documentation**. La especificación OpenAPI 3.0.3 también está disponible en **http://localhost:8080/openapi.json** y en el archivo [public/openapi.json](public/openapi.json).

Para probar la API en Swagger UI:

1. Ejecuta las migraciones y seeders descritos arriba.
2. Abre `POST /login`, pulsa **Try it out** y envía las credenciales de prueba.
3. Copia únicamente el valor de `token` de la respuesta.
4. Pulsa **Authorize**, pega el token sin anteponer `Bearer` y confirma.
5. Abre el endpoint deseado, pulsa **Try it out**, completa los datos y ejecuta la petición.

Swagger UI carga sus archivos JavaScript y CSS desde jsDelivr y requiere conexión a Internet para mostrarse. El contrato JSON se sirve desde la aplicación. Mantén `public/openapi.json` actualizado cuando cambien rutas, permisos o validaciones.

---

## Autenticacion

La API usa **Laravel Sanctum** con Bearer Token. Todos los endpoints (excepto login) requieren autenticacion.

### Login

```
POST /api/login
```

**Body (JSON):**

```json
{
  "email_username": "admin@example.com",
  "password": "password"
}
```

**Respuesta exitosa (200):**

```json
{
  "user": "Admin User",
  "token": "1|abc123...",
  "roles": ["admin"],
  "permissions": ["products.view", "products.create", "products.update", "products.delete", "products.view.price"],
  "message": "Login successful"
}
```

### Logout

```
POST /api/logout
Authorization: Bearer <token>
```

Revoca el token utilizado. El login acepta correo electronico y limita las solicitudes a 5 por minuto.

### Usuarios de prueba

| Email | Contrasena | Rol | Permisos |
|---|---|---|---|
| `admin@example.com` | `password` | admin | Todos |
| `operator@example.com` | `password` | editor | view, view.price, create, update |
| `viewer@example.com` | `password` | viewer | view, view.price |

---

## Endpoints de Productos

Todos los endpoints requieren el header:
```
Authorization: Bearer <token>
```

### Listar productos

```
GET /api/products?page=1&per_page=15
```

**Permiso requerido:** usuario autenticado (cualquier rol).

**Respuesta (200):** Paginador en la raiz con `data`, `current_page`, `last_page`, `per_page` y `total`. Tambien se acepta el parametro anterior `perPage`.

---

### Obtener un producto

```
GET /api/products/{id}
```

**Permiso requerido:** `products.view`

**Respuesta (200):**

```json
{
  "product": {
    "id": 1,
    "name": "Laptop HP Pavilion",
    "description": "...",
    "price": "799.99",
    "tax_cost": "120.00",
    "manufacturing_cost": "450.00",
    "divisa_id": 1,
    "divisa": { ... }
  }
}
```

---

### Crear un producto

```
POST /api/products
```

**Permiso requerido:** `products.create`

**Body (JSON):**

```json
{
  "name": "Nuevo Producto",
  "description": "Descripcion del producto",
  "price": 150.00,
  "tax_cost": 15.00,
  "manufacturing_cost": 80.00,
  "divisa_id": 1
}
```

**Campos opcionales:** `tax_cost`, `manufacturing_cost` (por defecto 0). Los importes aceptan hasta dos decimales y deben ser no negativos.

**Respuesta (201):**

```json
{
  "message": "Product created successfully.",
  "product": { ... }
}
```

---

### Actualizar un producto

```
PUT /api/products/{id}
```

**Permiso requerido:** `products.update`

**Body (JSON):** Cualquier campo a actualizar (actualizacion parcial soportada).

```json
{
  "name": "Nombre Actualizado",
  "price": 200.00
}
```

**Respuesta (200):**

```json
{
  "message": "Product updated successfully.",
  "product": { ... }
}
```

---

### Eliminar un producto

```
DELETE /api/products/{id}
```

**Permiso requerido:** `products.delete`

La eliminacion es logica (Soft Delete): el producto deja de aparecer en la API y sus precios se conservan en la base de datos.

**Respuesta (200):**

```json
{
  "message": "Product deleted successfully."
}
```

---

## Endpoints de Precios por Producto

### Listar precios de un producto

```
GET /api/products/{id}/prices?page=1&per_page=15
```

**Permiso requerido:** `products.view.price`

**Respuesta (200):** Lista paginada de precios con relaciones (producto y divisa).

---

### Crear un precio para un producto

```
POST /api/products/{id}/prices
```

**Permiso requerido:** `products.create`

**Body (JSON):**

```json
{
  "price": 120.50,
  "divisa_id": 2
}
```

**Respuesta (201):**

```json
{
  "message": "Product price created successfully.",
  "price": { ... }
}
```

---

## Codigos de Respuesta

| Codigo | Descripcion |
|---|---|
| `200` | Operacion exitosa |
| `201` | Recurso creado exitosamente |
| `401` | No autenticado (token faltante o invalido) |
| `403` | No autorizado (sin permiso suficiente) |
| `404` | Recurso no encontrado |
| `422` | Error de validacion |
| `429` | Limite de solicitudes excedido |

---

## Tests

Ejecutar la suite de pruebas:

```bash
docker compose exec app php artisan test
```

Ejecutar solo los tests de productos:

```bash
docker compose exec app php artisan test --filter="ProductControllerTest|ProductPriceControllerTest"
```

---

## Estructura del Proyecto

```
app/
  Http/
    Controllers/
      Auth/AuthController.php
      Products/ProductController.php
      Products/ProductPriceController.php
    Requests/Product/
      StoreProductRequest.php
      UpdateProductRequest.php
      StoreProductPriceRequest.php
  Models/Api/
    Product.php
    Currency/Divisas.php
    Detail/ProductPrice.php
  Repositories/Product/
    ProductRepository.php
    ProductPricesRepository.php
  Responsable/Api/
    Product/
      AllProductResponsable.php
      ShowProductResponsable.php
      CreateProductResponsable.php
      UpdateProductResponsable.php
      DeleteProductResponsable.php
      ListProductPricesResponsable.php
      CreateProductPriceResponsable.php
    Security/AuthResponsable.php
routes/
  api.php
  api_routes/
    auth/auth.php
    products/products.php
tests/Feature/
  ProductControllerTest.php
  ProductPriceControllerTest.php
```

## Licencia

Software de codigo abierto bajo la licencia [MIT](https://opensource.org/licenses/MIT).
