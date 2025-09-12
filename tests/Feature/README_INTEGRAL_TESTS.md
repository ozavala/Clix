# Pruebas Integrales del Sistema CRM-ERP

## Descripción General

Las pruebas integrales verifican el flujo completo del sistema Clix, asegurando que todos los componentes trabajen correctamente juntos. Estas pruebas sirven como validación del sistema completo y documentación de las relaciones entre entidades.

## Archivos de Pruebas

### `IntegralFlowTest.php`

**Ubicación:** `tests/Feature/IntegralFlowTest.php`

**Propósito:** Prueba integral que cubre todo el flujo del sistema desde la configuración inicial hasta la contabilidad.

## Componentes Verificados

### 1. Configuración Inicial
- ✅ Configuración de empresa (nombre, legal_id, cuenta bancaria)
- ✅ Creación de usuarios y roles del sistema

### 2. Gestión de Maestros
- ✅ **Proveedores** con legal_id requerido y único
- ✅ **Clientes** con legal_id requerido y único
- ✅ **Productos** con precios, costos y SKUs

### 3. Flujo de Compras
- ✅ **PurchaseOrder** (Órdenes de Compra)
  - Creación con proveedor, fechas y totales
  - Items con productos, cantidades y precios
  - Verificación de relaciones y totales

### 4. Flujo de Ventas
- ✅ **Invoice** (Facturas)
  - Creación con cliente, fechas y totales
  - Items con productos, cantidades y precios
  - Verificación de relaciones y totales

### 5. Flujo de Gastos
- ✅ **Bill** (Gastos/Facturas de Proveedores)
  - Creación con proveedor, fechas y totales
  - Items con descripciones, cantidades y precios
  - Verificación de relaciones y totales

### 6. Gestión de Pagos
- ✅ **Payment** para Facturas (Cobros)
  - Relación polimórfica con Invoice
  - Verificación de montos y métodos de pago
- ✅ **Payment** para Bills (Pagos a Proveedores)
  - Relación polimórfica con Bill
  - Verificación de montos y métodos de pago

### 7. Contabilidad
- ✅ **JournalEntry** (Asientos Contables)
  - Creación con fechas, referencias y descripciones
  - Tipos de transacción (payment, invoice, bill, adjustment)
- ✅ **JournalEntryLine** (Líneas de Asientos)
  - Códigos y nombres de cuentas
  - Montos de débito y crédito
  - Entidades relacionadas (clientes, proveedores)

### 8. Verificaciones Integrales
- ✅ **Legal IDs** en todos los documentos
- ✅ **Relaciones** entre entidades
- ✅ **Integridad** de datos
- ✅ **Balances** contables

## Métodos de Prueba

### `test_complete_integral_flow()`

**Propósito:** Verifica el flujo completo del sistema

**Pasos:**
1. Crear datos base (usuarios, proveedores, clientes, productos)
2. Crear Purchase Order con items
3. Crear Invoice con items
4. Crear Bill con items
5. Crear Payment para Invoice
6. Crear Payment para Bill
7. Verificar relaciones y legal_ids

**Verificaciones:**
- ✅ Documentos creados correctamente
- ✅ Relaciones polimórficas funcionando
- ✅ Legal IDs presentes en todas las entidades
- ✅ Pagos relacionados correctamente

### `test_journal_entries_creation()`

**Propósito:** Verifica la creación de asientos contables

**Pasos:**
1. Crear JournalEntry
2. Crear JournalEntryLine de débito
3. Crear JournalEntryLine de crédito
4. Verificar balances

**Verificaciones:**
- ✅ Asiento contable creado correctamente
- ✅ Líneas de asiento creadas correctamente
- ✅ Códigos de cuenta presentes
- ✅ Montos de débito y crédito correctos

## Factories Utilizados

### Factories Creados/Modificados:
- ✅ `JournalEntryFactory.php` - Asientos contables
- ✅ `JournalEntryLineFactory.php` - Líneas de asientos
- ✅ `SupplierFactory.php` - Proveedores con legal_id
- ✅ `PurchaseOrderItemFactory.php` - Items de órdenes de compra

### Factories Existentes Utilizados:
- ✅ `UserFactory.php` - Usuarios del sistema
- ✅ `CrmUserFactory.php` - Usuarios CRM
- ✅ `CustomerFactory.php` - Clientes
- ✅ `ProductFactory.php` - Productos
- ✅ `PurchaseOrderFactory.php` - Órdenes de compra
- ✅ `InvoiceFactory.php` - Facturas
- ✅ `InvoiceItemFactory.php` - Items de facturas
- ✅ `BillFactory.php` - Bills/Gastos
- ✅ `BillItemFactory.php` - Items de bills
- ✅ `PaymentFactory.php` - Pagos

## Configuración Requerida

### Settings Necesarios:
```php
Setting::updateOrCreate(['key' => 'company_name'], ['value' => 'Ingeconsersa SA', 'type' => 'custom']);
Setting::updateOrCreate(['key' => 'company_legal_id'], ['value' => '12345678-9', 'type' => 'custom']);
Setting::updateOrCreate(['key' => 'company_bank_account'], ['value' => 'Banco Central 12345678', 'type' => 'custom']);
```

### Migraciones Requeridas:
- ✅ Todas las migraciones del sistema ejecutadas
- ✅ Columna `legal_id` en `suppliers` (NOT NULL, UNIQUE)
- ✅ Configuración de empresa en `settings`

## Ejecución de Pruebas

### Ejecutar Prueba Específica:
```bash
php artisan test tests/Feature/IntegralFlowTest.php
```

### Ejecutar Todas las Pruebas:
```bash
php artisan test
```

### Ejecutar con Cobertura:
```bash
php artisan test --coverage
```

## Resultados Esperados

### Prueba Exitosa:
```
PASS  Tests\Feature\IntegralFlowTest
✓ complete integral flow
✓ journal entries creation

Tests: 2 passed (14 assertions)
Duration: 2.71s
```

### Verificaciones Pasadas:
- ✅ 14 assertions exitosas
- ✅ Flujo completo funcionando
- ✅ Contabilidad balanceada
- ✅ Legal IDs presentes
- ✅ Relaciones correctas

## Mantenimiento

### Agregar Nuevas Pruebas:
1. Crear método `test_nuevo_componente()`
2. Documentar el propósito en comentarios
3. Agregar verificaciones necesarias
4. Actualizar este README

### Modificar Pruebas Existentes:
1. Actualizar comentarios de documentación
2. Verificar que las relaciones sigan siendo correctas
3. Ejecutar pruebas para validar cambios
4. Actualizar este README si es necesario

## Troubleshooting

### Errores Comunes:

1. **Columna no existe:**
   - Verificar migraciones ejecutadas
   - Revisar estructura de tabla
   - Corregir factory o prueba

2. **Restricción única violada:**
   - Verificar legal_ids únicos
   - Revisar factories para duplicados
   - Usar `updateOrCreate` para settings

3. **Relación polimórfica fallida:**
   - Verificar nombres de columnas (`payable_type` vs `paymentable_type`)
   - Revisar modelo Payment
   - Corregir nombres en prueba

### Debugging:
```bash
# Verificar estructura de tabla
php artisan migrate:status

# Verificar settings
php artisan tinker --execute="App\Models\Setting::all(['key', 'value'])->each(function(\$s) { echo \$s->key . ' => ' . \$s->value . PHP_EOL; });"

# Ejecutar con verbose
php artisan test tests/Feature/IntegralFlowTest.php -v
```

## Referencias

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Database Factories](https://laravel.com/docs/factories)
- [Model Relationships](https://laravel.com/docs/eloquent-relationships)

---

**Última actualización:** Julio 2025  
**Versión:** 1.0  
**Autor:** Sistema Clix 