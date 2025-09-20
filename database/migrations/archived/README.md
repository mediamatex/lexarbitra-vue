# Archived Migrations and Models

These migrations and models have been archived due to architecture improvements:

## Tenant-Only Tables (moved to `database/migrations/tenant/`)
- `create_case_participants_table.php` - Case participants are stored in tenant databases only
- `create_case_parties_table.php` - Case parties are stored in tenant databases only
- `create_documents_table.php` - Documents are stored in tenant databases only

## Consolidated Migrations (merged into `case_references`)
- `create_case_files_table.php` - Merged into `case_references` table
- `create_case_database_connections_table.php` - Merged into `case_references` table
- `add_tenant_references_to_case_files_table.php` - Consolidated into main case_references migration
- `add_created_by_to_case_files_table.php` - Consolidated into main case_references migration

## Archived Models (replaced by `CaseReference`)
- `CaseFile.php` - Replaced by `CaseReference` model
- `CaseDatabaseConnection.php` - Functionality merged into `CaseReference` model

## Current Architecture

**Landlord Database Tables:**
- `users` - System users and authentication
- `case_references` - **Single unified table** with case info AND database connection details
- `cache` - Laravel cache
- `jobs` - Laravel queue jobs

**Tenant Database Tables (per case):**
- `case_files` - Full case data and details
- `case_participants` - Case participants
- `case_parties` - Case parties
- `documents` - Case documents

**Benefits of New Architecture:**
- ✅ Single table for case references eliminates joins
- ✅ No table name confusion between landlord and tenant
- ✅ Cleaner, more maintainable code
- ✅ Better performance with fewer tables