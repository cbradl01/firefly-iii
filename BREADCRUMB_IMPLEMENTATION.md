# Breadcrumb Implementation Guide

## Overview
This document outlines the breadcrumb implementation approach for Firefly III, including the challenges encountered and the solutions implemented for the Financial Entities feature.

## Breadcrumb System Architecture

### Current Implementation
Firefly III uses the `diglactic/laravel-breadcrumbs` package for breadcrumb management.

#### Package Configuration
```php
// composer.json
"diglactic/laravel-breadcrumbs": "^7.0"
```

#### Service Provider Registration
```php
// config/app.php
'providers' => [
    // ...
    Diglactic\Breadcrumbs\ServiceProvider::class,
    // ...
],
```

### Breadcrumb Definitions
Breadcrumbs are defined in `routes/breadcrumbs.php`:

```php
use FireflyIII\Models\FinancialEntity;

// Financial Entities breadcrumbs
Breadcrumbs::for(
    'financial-entities.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.financial_entities'), route('financial-entities.index'));
    }
);

Breadcrumbs::for(
    'financial-entities.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('financial-entities.index');
        $breadcrumbs->push(trans('firefly.create_financial_entity'), route('financial-entities.create'));
    }
);

Breadcrumbs::for(
    'financial-entities.show',
    static function (Generator $breadcrumbs, $id): void {
        $breadcrumbs->parent('financial-entities.index');
        $entity = FinancialEntity::find($id);
        if ($entity) {
            $breadcrumbs->push(limitStringLength($entity->display_name ?: $entity->name), route('financial-entities.show', [$id]));
        } else {
            $breadcrumbs->push(trans('firefly.financial_entity'), route('financial-entities.show', [$id]));
        }
    }
);

Breadcrumbs::for(
    'financial-entities.edit',
    static function (Generator $breadcrumbs, $id): void {
        $breadcrumbs->parent('financial-entities.show', $id);
        $breadcrumbs->push(trans('firefly.edit_financial_entity'), route('financial-entities.edit', [$id]));
    }
);
```

### Translation Keys
Breadcrumb titles are defined in `resources/lang/en_US/breadcrumbs.php`:

```php
return [
    // ... existing translations ...
    
    // Financial entities
    'financial_entities'     => 'Financial Entities',
    'create_financial_entity' => 'Create Financial Entity',
    'edit_financial_entity'  => 'Edit Financial Entity',
];
```

## Implementation Challenges

### Challenge 1: Dynamic Entity Names
**Problem**: Breadcrumbs need to display the actual entity name, not just a generic label.

**Initial Approach**: Use `Breadcrumbs.render()` in templates
```twig
{% block breadcrumbs %}
    {{ Breadcrumbs.render(Route.getCurrentRoute().getName(), financialEntity.id) }}
{% endblock %}
```

**Issues Encountered**:
- "Too few arguments" error when calling `Breadcrumbs.generate()`
- Route parameters not properly passed to breadcrumb definitions
- Template rendering order issues

### Challenge 2: Layout Integration
**Problem**: Breadcrumbs need to be displayed in the navbar, not the content area.

**Initial Approach**: Move breadcrumbs to navbar section
```twig
<!-- In navbar -->
<div class="navbar-breadcrumb">
    {{ Breadcrumbs.generate() }}
</div>
```

**Issues Encountered**:
- Dynamic breadcrumb generation failed due to missing parameters
- `app()` function not available in Twig templates
- View data not accessible in layout template

### Challenge 3: Data Sharing
**Problem**: Entity data needs to be available in the layout template for breadcrumb rendering.

**Attempted Solutions**:

#### Solution 1: Global View Sharing
```php
// In controller
app('view')->share('financialEntity', $financialEntity);
```

**Issues**: View sharing happens after layout rendering.

#### Solution 2: View Composer
```php
// ViewComposerServiceProvider
View::composer('layout.default', function ($view) {
    $routeName = Route::currentRouteName();
    
    if ($routeName === 'financial-entities.show' || $routeName === 'financial-entities.edit') {
        $entityId = Route::current()->parameter('id');
        
        if ($entityId) {
            $user = Auth::user();
            $financialEntity = FinancialEntity::whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->find($entityId);
            
            if ($financialEntity) {
                $view->with('financialEntity', $financialEntity);
            }
        }
    }
});
```

**Status**: Implemented but entity names still not displaying in breadcrumbs.

## Current Implementation

### Manual Breadcrumb Rendering
Due to the challenges with the breadcrumb package, a manual approach was implemented in the layout:

```twig
<!-- In layout/default.twig navbar -->
<div class="navbar-breadcrumb" style="display: inline-block; margin-left: 20px;">
    <ol class="breadcrumb" style="background: transparent; margin: 0; padding: 15px 0;">
        <li><a href="{{ route('index') }}" style="color: #fff;">Home</a></li>
        {% if current_route_name starts with 'financial-entities' %}
            <li><a href="{{ route('financial-entities.index') }}" style="color: #fff;">Financial Entities</a></li>
            {% if current_route_name == 'financial-entities.create' %}
                <li class="active" style="color: #fff;">Create Financial Entity</li>
            {% elseif current_route_name == 'financial-entities.show' %}
                {% if financialEntity is defined %}
                    <li class="active" style="color: #fff;">{{ financialEntity.display_name ?: financialEntity.name }}</li>
                {% else %}
                    <li class="active" style="color: #fff;">Financial Entity</li>
                {% endif %}
            {% elseif current_route_name == 'financial-entities.edit' %}
                {% if financialEntity is defined %}
                    <li><a href="{{ route('financial-entities.show', financialEntity.id) }}" style="color: #fff;">{{ financialEntity.display_name ?: financialEntity.name }}</a></li>
                    <li class="active" style="color: #fff;">Edit Financial Entity</li>
                {% else %}
                    <li class="active" style="color: #fff;">Edit Financial Entity</li>
                {% endif %}
            {% endif %}
        {% endif %}
    </ol>
</div>
```

### Content Area Breadcrumbs (Hidden)
The original breadcrumb section in the content area is hidden:

```twig
<section class="content-header" style="display: none;">
    {% include('partials.page-header') %}
    <div style="display: none;">
        {% block breadcrumbs %}{% endblock %}
    </div>
</section>
```

## Alternative Approaches

### Approach 1: Middleware-Based Solution
Create middleware that shares breadcrumb data globally:

```php
class BreadcrumbMiddleware
{
    public function handle($request, Closure $next)
    {
        $routeName = $request->route()->getName();
        
        if (str_starts_with($routeName, 'financial-entities')) {
            $this->prepareFinancialEntityBreadcrumbs($request);
        }
        
        return $next($request);
    }
    
    private function prepareFinancialEntityBreadcrumbs($request)
    {
        if (in_array($request->route()->getName(), ['financial-entities.show', 'financial-entities.edit'])) {
            $entityId = $request->route()->parameter('id');
            
            if ($entityId) {
                $user = Auth::user();
                $financialEntity = FinancialEntity::whereHas('users', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->find($entityId);
                
                if ($financialEntity) {
                    View::share('financialEntity', $financialEntity);
                }
            }
        }
    }
}
```

### Approach 2: Custom Breadcrumb Service
Create a dedicated service for breadcrumb management:

```php
class BreadcrumbService
{
    public function generateForRoute($routeName, $parameters = [])
    {
        switch ($routeName) {
            case 'financial-entities.show':
                return $this->generateFinancialEntityShowBreadcrumbs($parameters['id']);
            case 'financial-entities.edit':
                return $this->generateFinancialEntityEditBreadcrumbs($parameters['id']);
            default:
                return $this->generateDefaultBreadcrumbs($routeName);
        }
    }
    
    private function generateFinancialEntityShowBreadcrumbs($entityId)
    {
        $entity = FinancialEntity::find($entityId);
        
        return [
            ['title' => 'Home', 'url' => route('index')],
            ['title' => 'Financial Entities', 'url' => route('financial-entities.index')],
            ['title' => $entity ? ($entity->display_name ?: $entity->name) : 'Financial Entity', 'url' => null]
        ];
    }
}
```

### Approach 3: JavaScript-Based Solution
Use JavaScript to dynamically update breadcrumbs:

```javascript
function updateBreadcrumbs(routeName, entityData) {
    const breadcrumbContainer = document.querySelector('.navbar-breadcrumb ol');
    
    if (routeName === 'financial-entities.show') {
        breadcrumbContainer.innerHTML = `
            <li><a href="/">Home</a></li>
            <li><a href="/financial-entities">Financial Entities</a></li>
            <li class="active">${entityData.display_name || entityData.name}</li>
        `;
    }
}
```

## Recommended Solution

### Phase 1: Fix Current Implementation
1. **Debug View Composer**: Ensure the ViewComposerServiceProvider is working correctly
2. **Verify Route Parameters**: Check that route parameters are being passed correctly
3. **Test Data Availability**: Confirm that `financialEntity` variable is available in layout

### Phase 2: Implement Robust Solution
1. **Middleware Approach**: Implement breadcrumb middleware for consistent data sharing
2. **Service Layer**: Create dedicated breadcrumb service for complex logic
3. **Caching**: Cache breadcrumb data for better performance

### Phase 3: Package Integration
1. **Fix Package Usage**: Resolve issues with `diglactic/laravel-breadcrumbs` package
2. **Custom Extensions**: Extend package functionality if needed
3. **Migration**: Gradually migrate from manual implementation to package-based solution

## Testing Strategy

### Unit Tests
```php
class BreadcrumbServiceTest extends TestCase
{
    public function test_generates_financial_entity_breadcrumbs()
    {
        $entity = FinancialEntity::factory()->create([
            'name' => 'Test Entity',
            'display_name' => 'TE'
        ]);
        
        $breadcrumbs = app(BreadcrumbService::class)->generateForRoute('financial-entities.show', ['id' => $entity->id]);
        
        $this->assertCount(3, $breadcrumbs);
        $this->assertEquals('TE', $breadcrumbs[2]['title']);
    }
}
```

### Integration Tests
```php
class BreadcrumbIntegrationTest extends TestCase
{
    public function test_breadcrumbs_display_correctly()
    {
        $user = User::factory()->create();
        $entity = FinancialEntity::factory()->create();
        $entity->users()->attach($user);
        
        $response = $this->actingAs($user)
            ->get(route('financial-entities.show', $entity->id));
        
        $response->assertSee($entity->display_name);
    }
}
```

## Performance Considerations

### Database Queries
- Minimize database queries for breadcrumb data
- Use eager loading when possible
- Cache frequently accessed entity data

### Caching Strategy
```php
public function getBreadcrumbData($entityId)
{
    return Cache::remember("breadcrumb_entity_{$entityId}", 300, function () use ($entityId) {
        return FinancialEntity::select('id', 'name', 'display_name')
            ->find($entityId);
    });
}
```

## Future Enhancements

### 1. Dynamic Breadcrumb Generation
- Automatic breadcrumb generation based on route hierarchy
- Support for nested resources
- Custom breadcrumb templates

### 2. Breadcrumb Customization
- User-configurable breadcrumb display
- Custom breadcrumb separators
- Breadcrumb styling options

### 3. Advanced Features
- Breadcrumb history tracking
- Breadcrumb-based navigation
- Mobile-optimized breadcrumb display

## Troubleshooting Guide

### Common Issues

#### Issue: "Too few arguments" error
**Cause**: Route parameters not passed to breadcrumb definitions
**Solution**: Ensure route parameters are properly extracted and passed

#### Issue: Entity name not displaying
**Cause**: `financialEntity` variable not available in layout
**Solution**: Use ViewComposer or middleware to share data

#### Issue: Breadcrumbs not updating
**Cause**: Cache not invalidated on entity changes
**Solution**: Implement proper cache invalidation

### Debug Steps
1. Check if ViewComposerServiceProvider is registered
2. Verify route parameters are correct
3. Test database queries manually
4. Check view data availability
5. Validate breadcrumb template syntax

## Conclusion

The breadcrumb implementation for Financial Entities presents several challenges related to dynamic data sharing and package integration. While a manual approach has been implemented, a more robust solution using middleware and dedicated services is recommended for production use.

The key lessons learned:
1. Package integration can be complex with dynamic data
2. View data sharing requires careful consideration of rendering order
3. Manual implementations provide more control but require more maintenance
4. Testing is crucial for breadcrumb functionality
5. Performance considerations are important for user experience
