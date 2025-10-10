# Firefly III Development Roadmap

This document tracks planned improvements and optimizations for Firefly III, organized by priority and implementation timeline.

## 🚀 High Priority (Next 1-2 months)

### Account Field Validation System ✅ COMPLETED
- [x] Implement comprehensive field requirements for different account types
- [x] Add validation service for required vs optional fields
- [x] Integrate with AccountFactory and AccountUpdateService
- [x] Clean up legacy field arrays and configurations

### Product Name Display Enhancement ✅ COMPLETED
- [x] Hide account "name" column from accounts/all page
- [x] Display "product_name" instead of generic account types
- [x] Add product_name to valid field configurations
- [x] Update IndexController to retrieve product_name from meta fields

## 🔄 Medium Priority (Next 3-6 months)

### Database Schema Optimization
**Problem**: Current `account_meta` table uses EAV (Entity-Attribute-Value) pattern which causes:
- N+1 query problems when loading account metadata
- No indexing on specific metadata fields
- JSON parsing overhead for every access
- Poor query performance for filtering by metadata

**Proposed Solutions**:

#### Option 1: JSON Column Approach
```sql
ALTER TABLE accounts ADD COLUMN metadata JSON;
```
**Benefits**: Single query, JSON indexing, type safety
**Drawbacks**: Database-specific, complex queries

#### Option 2: Separate Tables per Account Type
```sql
CREATE TABLE asset_accounts (
    account_id INT PRIMARY KEY,
    account_role VARCHAR(50),
    institution VARCHAR(255),
    owner VARCHAR(100),
    product_name VARCHAR(255)
);

CREATE TABLE liability_accounts (
    account_id INT PRIMARY KEY,
    liability_direction VARCHAR(20),
    interest DECIMAL(5,2),
    interest_period VARCHAR(20)
);
```
**Benefits**: Type safety, performance, database constraints
**Drawbacks**: Multiple tables, complex joins

#### Option 3: Hybrid Approach (RECOMMENDED)
```sql
-- Structured tables for frequently-queried fields
CREATE TABLE account_core_metadata (
    account_id INT PRIMARY KEY,
    institution VARCHAR(255),
    owner VARCHAR(100),
    product_name VARCHAR(255),
    account_number VARCHAR(50)
);

CREATE TABLE account_liability_metadata (
    account_id INT PRIMARY KEY,
    liability_direction VARCHAR(20) NOT NULL,
    interest DECIMAL(5,2),
    interest_period VARCHAR(20)
);

-- JSON column for flexible/dynamic fields
ALTER TABLE accounts ADD COLUMN flexible_metadata JSON;
```
**Benefits**: Best performance + flexibility, migration-friendly
**Drawbacks**: Moderate complexity

**Implementation Steps**:
1. Create new metadata tables
2. Migrate existing account_meta data
3. Update AccountFactory to use new structure
4. Update queries to use new tables
5. Remove old account_meta table

### Account Classification System Integration
- [ ] Create database tables for account classification hierarchy
- [ ] Implement dynamic account type creation based on classification
- [ ] Add UI for managing account classifications
- [ ] Integrate with import processes

## 🔮 Low Priority (Future Considerations)

### Performance Optimizations
- [ ] Implement database query caching for account metadata
- [ ] Add database indexes for frequently-queried fields
- [ ] Optimize account listing queries with proper joins
- [ ] Implement lazy loading for account relationships

### UI/UX Improvements
- [ ] Add account type-specific forms with dynamic field validation
- [ ] Implement account classification browser/filter
- [ ] Add bulk account operations (edit, delete, reclassify)
- [ ] Create account import wizard with validation

### API Enhancements
- [ ] Add account metadata endpoints with proper filtering
- [ ] Implement account classification API
- [ ] Add bulk account operations API
- [ ] Create account validation API endpoints

### Data Migration Tools
- [ ] Create migration scripts for account_meta → new structure
- [ ] Add data validation tools for account integrity
- [ ] Implement account classification migration tools
- [ ] Create backup/restore tools for account data

## 📋 Technical Debt

### Code Cleanup
- [ ] Remove remaining references to legacy field arrays
- [ ] Consolidate account validation logic
- [ ] Standardize error handling across account services
- [ ] Add comprehensive unit tests for account validation

### Documentation
- [ ] Document new account field requirements system
- [ ] Create migration guide for database schema changes
- [ ] Add API documentation for account metadata
- [ ] Create troubleshooting guide for account issues

## 🎯 Success Metrics

### Performance Improvements
- [ ] Reduce account loading time by 50%
- [ ] Eliminate N+1 queries in account listings
- [ ] Improve account search/filter performance
- [ ] Reduce database query count for account operations

### Developer Experience
- [ ] Simplify account creation/update code
- [ ] Improve error messages for account validation
- [ ] Add better debugging tools for account issues
- [ ] Streamline account import processes

### User Experience
- [ ] Faster account page loading
- [ ] More intuitive account creation forms
- [ ] Better account organization and filtering
- [ ] Improved account import experience

---

## 📝 Notes

- **Database Schema Changes**: Will require careful migration planning and testing
- **Backward Compatibility**: Need to maintain compatibility during transition
- **Testing**: Comprehensive testing required for all database changes
- **Documentation**: Update all documentation when implementing changes

## 🔗 Related Issues/PRs

- Account field validation system implementation
- Product name display enhancement
- Database optimization research and planning

---

*Last Updated: [Current Date]*
*Next Review: [Next Review Date]*
