@if(\Simplify\Panel\Services\LegacyNavigation::topbarVisible())
    <a href="{{ \Simplify\Panel\Services\LegacyNavigation::url() }}" class="ps-legacy-return" title="Go to Simplify Panel" aria-label="Go to Simplify Panel">
        <x-tabler-layout-dashboard aria-hidden="true" />
        <span>Go to Simplify Panel</span>
    </a>
@endif
