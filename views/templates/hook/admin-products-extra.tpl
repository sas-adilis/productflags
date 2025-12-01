<div class="border rounded p-3 mt-3">
    {foreach from=$available_flags item=flag}
        <div class="product-flag-item">
            <span class="flag" style="color: {$flag['color']}; background-color: {$flag['background_color']};">{$flag.text}</span>
            <div class="btn-group btn-group-sm">
                <a href="#" class="btn btn-outline-secondary{if $flag.active == 1} active{/if} js-product-flag-btn" data-ajax-url="{$ajax_url}&active=1&id_product_flag={$flag.id_product_flag}">{l s='Enable' mod='productflags'}</a>
                <a href="#" class="btn btn-outline-secondary{if $flag.active == 0} active{/if} js-product-flag-btn" data-ajax-url="{$ajax_url}&active=0&id_product_flag={$flag.id_product_flag}">{l s='Disable' mod='productflags'}</a>
                <a href="#" class="btn btn-outline-secondary{if $flag.active == 'auto'} active{/if} js-product-flag-btn" data-ajax-url="{$ajax_url}&active=auto&id_product_flag={$flag.id_product_flag}">
                    {l s='Automatic' mod='productflags'} {if $flag.auto_criteria}( {$flag.auto_criteria} ){/if}
                </a>
            </div>
            <a href="{$link->getAdminLink('AdminProductFlag', true, [], ['updateproduct_flag' => 1, 'id_product_flag' => $flag.id_product_flag])}" target="_blank">
                <i class="material-icons">edit</i>
                {l s='Edit this flag' mod='productflags'}
            </a>
        </div>
    {/foreach}
</div>
<a href="{$link->getAdminLink('AdminProductFlag', true, [], ['addproduct_flag' => 1])}" class="btn btn-secondary mt-3" target="_blank">
    {l s='Create a new flag' mod='productflags'}
</a>