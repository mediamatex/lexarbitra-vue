# Archived Migrations

These migrations have been archived because they are no longer needed in the current tenant/landlord database architecture:

## Tenant-Only Tables (moved to `database/migrations/tenant/`)
- `create_case_participants_table.php` - Case participants are stored in tenant databases only
- `create_case_parties_table.php` - Case parties are stored in tenant databases only
- `create_documents_table.php` - Documents are stored in tenant databases only

## Redundant Landlord Migrations (consolidated into main migrations)
- `add_tenant_references_to_case_files_table.php` - Consolidated into main case_files migration
- `add_created_by_to_case_files_table.php` - Consolidated into main case_files migration

## Current Architecture

**Landlord Database Tables:**
- `users` - System users and authentication
- `case_files` - Minimal case references with connection info only
- `case_database_connections` - Database connection information
- `cache` - Laravel cache
- `jobs` - Laravel queue jobs

**Tenant Database Tables (per case):**
- `case_files` - Full case data and details
- `case_participants` - Case participants
- `case_parties` - Case parties
- `documents` - Case documents

This separation ensures clean isolation between cases while maintaining efficient reference tracking in the landlord database.