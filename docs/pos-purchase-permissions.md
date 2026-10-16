# Permisos POS de Compras

La migración `2026_10_11_000003_register_pos_purchase_permissions.php` registra
cinco acciones individuales en el módulo `compras`, vista `compras.documentos`:

| Código | Nombre |
| --- | --- |
| compra.crear | Crear compras |
| compra.modificar | Modificar compras |
| compra.ver | Ver compras |
| compra.eliminar | Eliminar compras |
| compra.recibir | Recibir compras |

El registro y la publicación del catálogo hacia HQ comparten una transacción.
Publica módulo y vista antes de las acciones; los permisos son globales, sin filtro
por stock. Repetir conserva IDs y asignaciones, no reactiva metadatos deshabilitados
y rechaza códigos vinculados a otros padres. No asigna permisos a usuarios ni roles.
ShAdmin utiliza los endpoints POS existentes para listar y asignar cada acción.

Desplegar explícitamente desde la API central:

```sh
php artisan migrate --path=database/migrations/2026_10_11_000003_register_pos_purchase_permissions.php
```

La migración requiere el catálogo jerárquico POS ya instalado. No ejecuta las demás
migraciones pendientes. HQ distribuye el catálogo y las asignaciones individuales
por el canal existente. La UI ShPOS conserva sus botones deshabilitados hasta que
sus flujos persistentes estén implementados; disponer del permiso no implementa
la operación de compra ni modifica existencias.

La definición local de estas acciones requiere la migración ShPOS
`203_purchase_permission_catalog.sql` en la base SQL Server compartida. Aplicarla
explícitamente con `tools/ShPOS.Database -- apply` antes de proyectar el catálogo.
Si HQ ya descargó los botones, conservar su entrega pendiente: la migración local
permite que su siguiente reintento complete la proyección y el ACK. No reiniciar
el espejo ni su secuencia. `inspect-central-permissions` muestra solo conteos para
comprobar los cinco códigos en HQ, `pos_permiso` y `pos_boton`.
