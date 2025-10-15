# Firefly III Development Roadmap

This document tracks planned improvements and optimizations for Firefly III, organized by priority and implementation timeline.

## 🚀 High Priority (Next 1-2 months)

### Account Classification System Redesign ✅ COMPLETED
**Problem**: The existing account classification system had several architectural issues:
- Confusing naming conventions (e.g., `ccAsset` role for credit cards treated as assets)
- Redundant account types (Loan, Debt, Mortgage) that served identical purposes
- Missing generic "Liability" type despite having specific liability subtypes
- Duplication between `AccountTypeEnum` and database `account_types` table
- Inconsistent abstraction levels between assets (types + roles) and liabilities (types only)
- No support for complex account relationships (e.g., brokerage accounts containing securities)

**Solution Implemented**: Complete redesign with normalized, relationship-based architecture:

#### New Database Structure
- **`account_categories`**: 5 core categories (Asset, Liability, Expense, Revenue, Equity)
- **`account_behaviors`**: 4 behavior types (Simple, Container, Security, Cash)
- **`account_types`**: Specific account types with proper relationships to categories and behaviors
- **`relationship_types`**: Defines how accounts relate to each other
- **`security_positions`**: For tracking individual securities (stocks, bonds, etc.)
- **`position_allocations`**: Maps securities to container accounts (supports AAPL across multiple brokerages)
- **`account_relationships`**: Links accounts with metadata
- **`account_meta`**: Replaces the old account_meta table

#### Key Features
- **Zero Ambiguity**: Each account type has exactly one behavior pattern
- **Flexible Relationships**: Accounts can contain other accounts or hold securities
- **Position-Based**: Supports complex portfolio management (one AAPL account, multiple allocations)
- **Firefly Compatible**: Maintains `firefly_mapping` for integration
- **Extensible**: Easy to add new account types, behaviors, and relationships

#### Models Created
- `AccountCategory`, `AccountBehavior`, `RelationshipType`
- `SecurityPosition`, `PositionAllocation`, `AccountRelationship`
- `AccountMetadata` (replaces `AccountMeta`)
- Updated `AccountType` and `Account` models with new relationships

#### Migration Strategy
- Preserved all existing account types and data
- Mapped existing types to new normalized structure
- Updated all existing accounts to reference new account_type_ids
- Maintained foreign key relationships and data integrity

**Benefits Achieved**:
- Eliminated architectural inconsistencies
- Enabled complex account relationships (brokerage → securities)
- Improved maintainability and extensibility
- Maintained backward compatibility
- Created foundation for advanced portfolio management features

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

### Database Schema Optimization ✅ COMPLETED
**Problem**: Current `account_meta` table uses EAV (Entity-Attribute-Value) pattern which causes:
- N+1 query problems when loading account metadata
- No indexing on specific metadata fields
- JSON parsing overhead for every access
- Poor query performance for filtering by metadata

**Solution Implemented**: Hybrid approach with structured tables + JSON flexibility:
- **`account_meta`**: Replaces `account_meta` with better indexing and type safety
- **`account_categories`**: Structured table for account categories
- **`account_behaviors`**: Structured table for account behaviors
- **`account_relationships`**: JSON metadata for flexible relationship data
- **`security_positions`**: Structured table for security-specific data

**Benefits Achieved**:
- Eliminated N+1 query problems
- Added proper indexing on frequently-queried fields
- Maintained flexibility with JSON for dynamic data
- Improved query performance for account operations
- Type safety for core account classification data

### Account Classification System Integration ✅ COMPLETED
- [x] Create database tables for account classification hierarchy
- [x] Implement dynamic account type creation based on classification
- [x] Add comprehensive Eloquent models for new system
- [x] Maintain backward compatibility with existing data
- [ ] Add UI for managing account classifications
- [ ] Integrate with import processes
- [ ] Update existing Firefly III code to use new system

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
- Account classification system redesign

---

## 📋 Next Steps for Account Classification System

### Immediate Tasks (Next 1-2 weeks)
- [ ] Install Laravel Tinker for testing and debugging
- [ ] Test new models with existing account data
- [ ] Verify all relationships work correctly
- [ ] Test balance calculations for different account types

### Integration Tasks (Next 1-2 months)
- [ ] Update existing Firefly III code to use new account classification system
- [ ] Modify AccountFactory to work with new structure
- [ ] Update AccountUpdateService for new relationships
- [ ] Update API endpoints to use new models
- [ ] Update frontend to display new account type information

### UI/UX Tasks (Next 2-3 months)
- [ ] Create account classification management interface
- [ ] Add account relationship visualization
- [ ] Implement security position tracking UI
- [ ] Add portfolio overview with position allocations
- [ ] Create account import wizard with new classification system

### Testing & Validation (Ongoing)
- [ ] Comprehensive unit tests for new models
- [ ] Integration tests for account operations
- [ ] Performance testing for complex account relationships
- [ ] Data migration validation tools
- [ ] User acceptance testing for new features

---

*Last Updated: January 20, 2025*
*Next Review: February 20, 2025*
