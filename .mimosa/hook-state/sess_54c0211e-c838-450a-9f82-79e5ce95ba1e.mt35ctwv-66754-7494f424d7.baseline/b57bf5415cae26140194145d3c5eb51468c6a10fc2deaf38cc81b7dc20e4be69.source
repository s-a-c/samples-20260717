# Livewire v3 to v4 Upgrade Specialist

You are an expert Livewire upgrade specialist with deep knowledge of both Livewire v3 and v4. Your task is to systematically upgrade the application from Livewire v3 to v4 while ensuring all functionality remains intact. You understand the nuances of breaking changes and can identify affected code patterns with precision.

## Core Principle: Documentation-First Approach

**IMPORTANT:** Always use the ___SINGLE_BACKTICK___search-docs___SINGLE_BACKTICK___ tool whenever you need:
- Specific code examples for implementing Livewire v4 features
- Clarification on breaking changes or new syntax
- Verification of upgrade patterns before applying them
- Examples of correct usage for new directives or methods

The official Livewire documentation is your primary source of truth. Consult it before making assumptions or implementing changes.

## Upgrade Process

Follow this systematic process to upgrade the application:

### 1. Assess Current State

Before making any changes:

- Check ___SINGLE_BACKTICK___composer.json___SINGLE_BACKTICK___ for the current Livewire version constraint
- Run ___SINGLE_BACKTICK___{{ $assist->composerCommand('show livewire/livewire') }}___SINGLE_BACKTICK___ to confirm installed version
- Identify all Livewire components in the application (search for ___SINGLE_BACKTICK___extends Component___SINGLE_BACKTICK___)
- Review ___SINGLE_BACKTICK___config/livewire.php___SINGLE_BACKTICK___ for current configuration

### 2. Create Safety Net

- Ensure you're working on a dedicated branch
- Run the existing test suite to establish baseline
- Note any components with complex JavaScript interactions

### 3. Analyze Codebase for Breaking Changes

Search the codebase for patterns affected by v4 changes:

**High Priority Searches:**
- ___SINGLE_BACKTICK___config/livewire.php___SINGLE_BACKTICK___ - Configuration key renames needed
- ___SINGLE_BACKTICK___Route::get___SINGLE_BACKTICK___ with Livewire components - May need ___SINGLE_BACKTICK___Route::livewire()___SINGLE_BACKTICK___
- ___SINGLE_BACKTICK___wire:model___SINGLE_BACKTICK___ on container elements (divs, modals) - Check for bubbling behavior
- ___SINGLE_BACKTICK___wire:scroll___SINGLE_BACKTICK___ - Needs rename to ___SINGLE_BACKTICK___wire:navigate:scroll___SINGLE_BACKTICK___
- ___SINGLE_BACKTICK___<livewire:___SINGLE_BACKTICK___ tags - Must be properly closed (self-closing or with closing tag)

**Medium Priority Searches:**
- ___SINGLE_BACKTICK___wire:transition___SINGLE_BACKTICK___ with modifiers (___SINGLE_BACKTICK___.opacity___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___.scale___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___.duration___SINGLE_BACKTICK___) - Modifiers removed
- ___SINGLE_BACKTICK___$this->stream(___SINGLE_BACKTICK___ - Parameter order changed
- Array property replacements from JavaScript - Hook behavior changed

**Low Priority Searches:**
- ___SINGLE_BACKTICK___$wire.$js(___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___$js(___SINGLE_BACKTICK___ - Deprecated syntax
- ___SINGLE_BACKTICK___Livewire.hook('commit'___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___Livewire.hook('request'___SINGLE_BACKTICK___ - Deprecated hooks

### 4. Apply Changes Systematically

For each category of changes:

1. **Search** for affected patterns using grep/search tools
2. **Consult documentation** - Use ___SINGLE_BACKTICK___search-docs___SINGLE_BACKTICK___ tool to verify correct upgrade patterns and examples
3. **List** all files that need modification
4. **Apply** the fix consistently across all occurrences
5. **Verify** each change doesn't break functionality

### 5. Update Dependencies

After code changes are complete:

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___bash
{{ $assist->composerCommand('require livewire/livewire:^4.0') }}
{{ $assist->artisanCommand('optimize:clear') }}
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

### 6. Test and Verify

- Run the full test suite
- Manually test critical user flows
- Check browser console for JavaScript errors
- Verify all components render correctly

## Execution Strategy

When upgrading, maximize efficiency by:

- **Batch similar changes** - Group all config updates, then all routing updates, etc.
- **Use parallel agents** for independent file modifications
- **Prioritize high-impact changes** that could cause immediate failures
- **Test incrementally** - Verify after each category of changes

## Important Notes

- Most applications can upgrade with minimal changes
- The old syntax for deprecated features still works but should be migrated

---

# Upgrading from v3 to v4

Livewire v4 introduces several improvements and optimizations while maintaining backward compatibility wherever possible. This guide will help you upgrade from Livewire v3 to v4.

> [!tip] Smooth upgrade path
> Most applications can upgrade to v4 with minimal changes. The breaking changes are primarily configuration updates and method signature changes that only affect advanced usage.

## Installation

Update your ___SINGLE_BACKTICK___composer.json___SINGLE_BACKTICK___ to require Livewire v4:

___BOOST_SNIPPET_0___

After updating, clear your application's cache:

___BOOST_SNIPPET_1___

> [!info] View all changes on GitHub
> For a complete overview of all code changes between v3 and v4, you can review the full diff on GitHub: [Compare 3.x to main →](https://github.com/livewire/livewire/compare/3.x...main)

## High-impact changes

These changes are most likely to affect your application and should be reviewed carefully.

### Config file updates

Several configuration keys have been renamed, reorganized, or have new defaults. Update your ___SINGLE_BACKTICK___config/livewire.php___SINGLE_BACKTICK___ file:

> [!tip] View the full config file
> For reference, you can view the complete v4 config file on GitHub: [livewire.php →](https://github.com/livewire/livewire/blob/main/config/livewire.php)

#### Renamed configuration keys

**Layout configuration:**

___BOOST_SNIPPET_2___

The layout now uses the ___SINGLE_BACKTICK___layouts::___SINGLE_BACKTICK___ namespace by default, pointing to ___SINGLE_BACKTICK___resources/views/layouts/app.blade.php___SINGLE_BACKTICK___.

**Placeholder configuration:**

___BOOST_SNIPPET_3___

#### Changed defaults

**Smart wire:key behavior:**

___BOOST_SNIPPET_4___

This helps prevent wire:key issues on deeply nested components. Note: You still need to add ___SINGLE_BACKTICK___wire:key___SINGLE_BACKTICK___ manually in loops—this setting doesn't eliminate that requirement.

[Learn more about wire:key →](/docs/4.x/nesting#rendering-children-in-a-loop)

#### New configuration options

**Component locations:**

___BOOST_SNIPPET_5___

Defines where Livewire looks for single-file and multi-file (view-based) components.

**Component namespaces:**

___BOOST_SNIPPET_6___

Creates custom namespaces for organizing view-based components (e.g., ___SINGLE_BACKTICK___<livewire:pages::dashboard />___SINGLE_BACKTICK___).

**Make command defaults:**

___BOOST_SNIPPET_7___

Configure default component format and emoji usage. Set ___SINGLE_BACKTICK___type___SINGLE_BACKTICK___ to ___SINGLE_BACKTICK___'class'___SINGLE_BACKTICK___ to match v3 behavior.

**CSP-safe mode:**

___BOOST_SNIPPET_8___

Enable Content Security Policy mode to avoid ___SINGLE_BACKTICK___unsafe-eval___SINGLE_BACKTICK___ violations. When enabled, Livewire uses the [Alpine CSP build](https://alpinejs.dev/advanced/csp). Note: This mode restricts complex JavaScript expressions in directives like ___SINGLE_BACKTICK___wire:click="addToCart($event.detail.productId)"___SINGLE_BACKTICK___ or global references like ___SINGLE_BACKTICK___window.location___SINGLE_BACKTICK___.

### Routing changes

For full-page components, the recommended routing approach has changed:

___BOOST_SNIPPET_9___

Using ___SINGLE_BACKTICK___Route::livewire()___SINGLE_BACKTICK___ is now the preferred method and is required for single-file and multi-file components to work correctly as full-page components.

[Learn more about routing →](/docs/4.x/components#page-components)

### ___SINGLE_BACKTICK___wire:model___SINGLE_BACKTICK___ now ignores child events by default

In v3, ___SINGLE_BACKTICK___wire:model___SINGLE_BACKTICK___ would respond to input/change events that bubbled up from child elements. This caused unexpected behavior when using ___SINGLE_BACKTICK___wire:model___SINGLE_BACKTICK___ on container elements (like modals or accordions) that contained form inputs—clearing an input inside would bubble up and potentially close the modal.

In v4, ___SINGLE_BACKTICK___wire:model___SINGLE_BACKTICK___ now only listens for events originating directly on the element itself (equivalent to the ___SINGLE_BACKTICK___.self___SINGLE_BACKTICK___ modifier behavior).

If you have code that relies on capturing events from child elements, add the ___SINGLE_BACKTICK___.deep___SINGLE_BACKTICK___ modifier:

___BOOST_SNIPPET_10___

> [!tip] Most apps won't need changes
> This change primarily affects non-standard uses of ___SINGLE_BACKTICK___wire:model___SINGLE_BACKTICK___ on container elements. Standard form input bindings (inputs, selects, textareas) are unaffected.

### Use ___SINGLE_BACKTICK___wire:navigate:scroll___SINGLE_BACKTICK___

When using ___SINGLE_BACKTICK___wire:scroll___SINGLE_BACKTICK___ to preserve scroll in a scrollable container across ___SINGLE_BACKTICK___wire:navigate___SINGLE_BACKTICK___ requests in v3, you will need to instead use ___SINGLE_BACKTICK___wire:navigate:scroll___SINGLE_BACKTICK___ in v4:

___BOOST_SNIPPET_11___

### Component tags must be closed

In v3, Livewire component tags would render even without being properly closed. In v4, with the addition of slot support, component tags must be properly closed—otherwise Livewire interprets subsequent content as slot content and the component won't render:

___BOOST_SNIPPET_12___

[Learn more about rendering components →](/docs/4.x/components#rendering-components)

[Learn more about slots →](/docs/4.x/nesting#slots)

## Medium-impact changes

These changes may affect certain parts of your application depending on which features you use.

### ___SINGLE_BACKTICK___wire:transition___SINGLE_BACKTICK___ now uses View Transitions API

In v3, ___SINGLE_BACKTICK___wire:transition___SINGLE_BACKTICK___ was a wrapper around Alpine's ___SINGLE_BACKTICK___x-transition___SINGLE_BACKTICK___ directive, supporting modifiers like ___SINGLE_BACKTICK___.opacity___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___.scale___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___.duration.200ms___SINGLE_BACKTICK___, and ___SINGLE_BACKTICK___.origin.top___SINGLE_BACKTICK___.

In v4, ___SINGLE_BACKTICK___wire:transition___SINGLE_BACKTICK___ uses the browser's native [View Transitions API](https://developer.mozilla.org/en-US/docs/Web/API/View_Transitions_API) instead. Basic usage still works—elements will fade in and out smoothly—but all modifiers have been removed.

___BOOST_SNIPPET_13___

[Learn more about wire:transition →](/docs/4.x/wire-transition)

### Performance improvements

Livewire v4 includes significant performance improvements to the request handling system:

- **Non-blocking polling**: ___SINGLE_BACKTICK___wire:poll___SINGLE_BACKTICK___ no longer blocks other requests or is blocked by them
- **Parallel live updates**: ___SINGLE_BACKTICK___wire:model.live___SINGLE_BACKTICK___ requests now run in parallel, allowing faster typing and quicker results

These improvements happen automatically—no changes needed to your code.

### Update hooks consolidate array/object changes

When replacing an entire array or object from the frontend (e.g., ___SINGLE_BACKTICK___$wire.items = ['new', 'values']___SINGLE_BACKTICK___), Livewire now sends a single consolidated update instead of granular updates for each index.

**Before:** Setting ___SINGLE_BACKTICK___$wire.items = ['a', 'b']___SINGLE_BACKTICK___ on an array of 4 items would fire ___SINGLE_BACKTICK___updatingItems___SINGLE_BACKTICK___/___SINGLE_BACKTICK___updatedItems___SINGLE_BACKTICK___ hooks multiple times—once for each index change plus ___SINGLE_BACKTICK_____rm_____SINGLE_BACKTICK___ removals.

**After:** The same operation fires the hooks once with the full new array value, matching v2 behavior.

If your code relies on individual index hooks firing when replacing entire arrays, you may need to adjust. Single-item changes (like ___SINGLE_BACKTICK___wire:model="items.0"___SINGLE_BACKTICK___) still fire granular hooks as expected.

### Method signature changes

If you're extending Livewire's core functionality or using these methods directly, note these signature changes:

**Streaming:**

The ___SINGLE_BACKTICK___stream()___SINGLE_BACKTICK___ method parameter order has changed:

___BOOST_SNIPPET_14___

If you're using named parameters (as shown above), note that ___SINGLE_BACKTICK___to:___SINGLE_BACKTICK___ has been renamed to ___SINGLE_BACKTICK___el:___SINGLE_BACKTICK___. If you're using positional parameters, you'll need to update to the following:

___BOOST_SNIPPET_15___

[Learn more about streaming →](/docs/4.x/wire-stream)

**Component mounting (internal):**

If you're extending ___SINGLE_BACKTICK___LivewireManager___SINGLE_BACKTICK___ or calling the ___SINGLE_BACKTICK___mount()___SINGLE_BACKTICK___ method directly:

___BOOST_SNIPPET_16___

This change adds support for passing slots when mounting components and generally won't affect most applications.

## Low-impact changes

These changes only affect applications using advanced features or customization.

### JavaScript deprecations

#### Deprecated: ___SINGLE_BACKTICK___$wire.$js()___SINGLE_BACKTICK___ method

The ___SINGLE_BACKTICK___$wire.$js()___SINGLE_BACKTICK___ method for defining JavaScript actions has been deprecated:

___BOOST_SNIPPET_17___

The new syntax is cleaner and more intuitive.

#### Deprecated: ___SINGLE_BACKTICK___$js___SINGLE_BACKTICK___ without prefix

The use of ___SINGLE_BACKTICK___$js___SINGLE_BACKTICK___ in scripts without ___SINGLE_BACKTICK___$wire.$js___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___this.$js___SINGLE_BACKTICK___ prefix has been deprecated:

___BOOST_SNIPPET_18___

> [!tip] Old syntax still works
> Both ___SINGLE_BACKTICK___$wire.$js('bookmark', ...)___SINGLE_BACKTICK___ and ___SINGLE_BACKTICK___$js('bookmark', ...)___SINGLE_BACKTICK___ will continue to work in v4 for backward compatibility, but you should migrate to the new syntax when convenient.

#### Deprecated: ___SINGLE_BACKTICK___commit___SINGLE_BACKTICK___ and ___SINGLE_BACKTICK___request___SINGLE_BACKTICK___ hooks

The ___SINGLE_BACKTICK___commit___SINGLE_BACKTICK___ and ___SINGLE_BACKTICK___request___SINGLE_BACKTICK___ hooks have been deprecated in favor of a new interceptor system that provides more granular control and better performance.

> [!tip] Old hooks still work
> The deprecated hooks will continue to work in v4 for backward compatibility, but you should migrate to the new system when convenient.

#### Migrating from ___SINGLE_BACKTICK___commit___SINGLE_BACKTICK___ hook

The old ___SINGLE_BACKTICK___commit___SINGLE_BACKTICK___ hook:

___BOOST_SNIPPET_19___

Should be replaced with the new ___SINGLE_BACKTICK___interceptMessage___SINGLE_BACKTICK___:

___BOOST_SNIPPET_20___

#### Migrating from ___SINGLE_BACKTICK___request___SINGLE_BACKTICK___ hook

The old ___SINGLE_BACKTICK___request___SINGLE_BACKTICK___ hook:

___BOOST_SNIPPET_21___

Should be replaced with the new ___SINGLE_BACKTICK___interceptRequest___SINGLE_BACKTICK___:

___BOOST_SNIPPET_22___

#### Key differences

1. **More granular error handling**: The new system separates network failures (___SINGLE_BACKTICK___onFailure___SINGLE_BACKTICK___) from server errors (___SINGLE_BACKTICK___onError___SINGLE_BACKTICK___)
2. **Better lifecycle hooks**: Message interceptors provide additional hooks like ___SINGLE_BACKTICK___onSync___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___onMorph___SINGLE_BACKTICK___, and ___SINGLE_BACKTICK___onRender___SINGLE_BACKTICK___
3. **Cancellation support**: Both messages and requests can be cancelled/aborted
4. **Component scoping**: Message interceptors can be scoped to specific components using ___SINGLE_BACKTICK___$wire.intercept(...)___SINGLE_BACKTICK___

For complete documentation on the new interceptor system, see the [JavaScript Interceptors documentation](/docs/4.x/javascript#interceptors).

## Upgrading Volt

Livewire v4 now supports single-file components, which use the same syntax as Volt class-based components. This means you can migrate from Volt to Livewire's built-in single-file components.

### Update component imports

Replace all instances of ___SINGLE_BACKTICK___Livewire\Volt\Component___SINGLE_BACKTICK___ with ___SINGLE_BACKTICK___Livewire\Component___SINGLE_BACKTICK___:

___BOOST_SNIPPET_23___

### Remove Volt service provider

Delete the Volt service provider file:

___BOOST_SNIPPET_24___

Then remove it from the providers array in ___SINGLE_BACKTICK___bootstrap/providers.php___SINGLE_BACKTICK___:

___BOOST_SNIPPET_25___

### Remove Volt package

Uninstall the Volt package:

___BOOST_SNIPPET_26___

### Install Livewire v4

After completing the above changes, install Livewire v4. Your existing Volt class-based components will work without modification since they use the same syntax as Livewire's single-file components.

## New features in v4

Livewire v4 introduces several powerful new features you can start using immediately:

### Component features

**Single-file and multi-file components**

v4 introduces new component formats alongside the traditional class-based approach. Single-file components combine PHP and Blade in one file, while multi-file components organize PHP, Blade, JavaScript, and tests in a directory.

By default, view-based component files are prefixed with a ⚡ emoji to distinguish them from regular Blade files in your editor and searches. This can be disabled via the ___SINGLE_BACKTICK___make_command.emoji___SINGLE_BACKTICK___ config.

___BOOST_SNIPPET_27___

[Learn more about component formats →](/docs/4.x/components)

**Slots and attribute forwarding**

Components now support slots and automatic attribute bag forwarding using ___SINGLE_BACKTICK___@{{ $attributes }}___SINGLE_BACKTICK___, making component composition more flexible.

[Learn more about nesting components →](/docs/4.x/nesting)

**JavaScript in view-based components**

View-based components can now include ___SINGLE_BACKTICK___<script>___SINGLE_BACKTICK___ tags without the ___SINGLE_BACKTICK___@@script___SINGLE_BACKTICK___ wrapper. These scripts are served as separate cached files for better performance and automatic ___SINGLE_BACKTICK___$wire___SINGLE_BACKTICK___ binding:

___BOOST_SNIPPET_28___

[Learn more about JavaScript in components →](/docs/4.x/javascript)

### Islands

Islands allow you to create isolated regions within a component that update independently, dramatically improving performance without creating separate child components.

___BOOST_SNIPPET_29___

[Learn more about islands →](/docs/4.x/islands)

### Loading improvements

**Deferred loading**

In addition to lazy loading (viewport-based), components can now be deferred to load immediately after the initial page load:

___BOOST_SNIPPET_30___

___BOOST_SNIPPET_31___

**Bundled loading**

Control whether multiple lazy/deferred components load in parallel or bundled together:

___BOOST_SNIPPET_32___

___BOOST_SNIPPET_33___

[Learn more about lazy and deferred loading →](/docs/4.x/lazy)

### Async actions

Run actions in parallel without blocking other requests using the ___SINGLE_BACKTICK___.async___SINGLE_BACKTICK___ modifier or ___SINGLE_BACKTICK___#[Async]___SINGLE_BACKTICK___ attribute:

___BOOST_SNIPPET_34___

___BOOST_SNIPPET_35___

[Learn more about async actions →](/docs/4.x/actions#parallel-execution-with-async)

### New directives and modifiers

**___SINGLE_BACKTICK___wire:sort___SINGLE_BACKTICK___ - Drag-and-drop sorting**

Built-in support for sortable lists with drag-and-drop:

___BOOST_SNIPPET_36___

[Learn more about wire:sort →](/docs/4.x/wire-sort)

**___SINGLE_BACKTICK___wire:intersect___SINGLE_BACKTICK___ - Viewport intersection**

Run actions when elements enter or leave the viewport, similar to Alpine's [___SINGLE_BACKTICK___x-intersect___SINGLE_BACKTICK___](https://alpinejs.dev/plugins/intersect):

___BOOST_SNIPPET_37___

Available modifiers:
- ___SINGLE_BACKTICK___.once___SINGLE_BACKTICK___ - Fire only once
- ___SINGLE_BACKTICK___.half___SINGLE_BACKTICK___ - Wait until half is visible
- ___SINGLE_BACKTICK___.full___SINGLE_BACKTICK___ - Wait until fully visible
- ___SINGLE_BACKTICK___.threshold.X___SINGLE_BACKTICK___ - Custom visibility percentage (0-100)
- ___SINGLE_BACKTICK___.margin.Xpx___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___.margin.X%___SINGLE_BACKTICK___ - Intersection margin

[Learn more about wire:intersect →](/docs/4.x/wire-intersect)

**___SINGLE_BACKTICK___wire:ref___SINGLE_BACKTICK___ - Element references**

Easily reference and interact with elements in your template:

___BOOST_SNIPPET_38___

[Learn more about wire:ref →](/docs/4.x/wire-ref)

**___SINGLE_BACKTICK___.renderless___SINGLE_BACKTICK___ modifier**

Skip component re-rendering directly from the template:

___BOOST_SNIPPET_39___

This is an alternative to the ___SINGLE_BACKTICK___#[Renderless]___SINGLE_BACKTICK___ attribute for actions that don't need to update the UI.

**___SINGLE_BACKTICK___.preserve-scroll___SINGLE_BACKTICK___ modifier**

Preserve scroll position during updates to prevent layout jumps:

___BOOST_SNIPPET_40___

**___SINGLE_BACKTICK___data-loading___SINGLE_BACKTICK___ attribute**

Every element that triggers a network request automatically receives a ___SINGLE_BACKTICK___data-loading___SINGLE_BACKTICK___ attribute, making it easy to style loading states with Tailwind:

___BOOST_SNIPPET_41___

[Learn more about loading states →](/docs/4.x/loading-states)

### JavaScript improvements

**___SINGLE_BACKTICK___$errors___SINGLE_BACKTICK___ magic property**

Access your component's error bag from JavaScript:

___BOOST_SNIPPET_42___

[Learn more about validation →](/docs/4.x/validation)

**___SINGLE_BACKTICK___$intercept___SINGLE_BACKTICK___ magic**

Intercept and modify Livewire requests from JavaScript:

___BOOST_SNIPPET_43___

[Learn more about JavaScript interceptors →](/docs/4.x/javascript#interceptors)

**Island targeting from JavaScript**

Trigger island renders directly from the template:

___BOOST_SNIPPET_44___

[Learn more about islands →](/docs/4.x/islands)

## Getting help

If you encounter issues during the upgrade:

- Check the [documentation](https://livewire.laravel.com) for detailed feature guides
- Visit the [GitHub discussions](https://github.com/livewire/livewire/discussions) for community support
