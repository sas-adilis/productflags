{extends file="helpers/form/form.tpl"}

{block name="input"}
    {if $input.type == 'conditions'}
        <div id="conditions" role="tablist" aria-multiselectable="false">
            <div class="list-group">
                <button type="button" class="list-group-item is-active" data-toggle="collapse" data-parent="#conditions" data-target="#conditionCategories">{l s='Categories' mod='productflags'}</button>
                {if count($input.manufacturers)}<button type="button" class="list-group-item" data-toggle="collapse" data-parent="#conditions" data-target="#conditionManufacturer">{l s='Manufacturer' mod='productflags'}</button>{/if}
                {if count($input.suppliers)}<button type="button" class="list-group-item" data-toggle="collapse" data-parent="#conditions" data-target="#conditionSupplier">{l s='Supplier' mod='productflags'}</button>{/if}
                <button type="button" class="list-group-item" data-toggle="collapse" data-parent="#conditions" data-target="#conditionState">{l s='Status' mod='productflags'}</button>
                <button type="button" class="list-group-item" data-toggle="collapse" data-parent="#conditions" data-target="#conditionQuantity">{l s='Stock' mod='productflags'}</button>
                <button type="button" class="list-group-item" data-toggle="collapse" data-parent="#conditions" data-target="#conditionProduct">{l s='Products include/exclude' mod='productflags'}</button>
            </div>
            <div class="condition-collapse collapse in" id="conditionCategories">
                <label for="conditions_category">{l s='Select at leat one category' mod='productflags'}</label>
                <select name="conditions[category][]" id="conditions_category" class="chosen" multiple="multiple">
                    {foreach $input.categories as $category}
                        <option value="{$category.id_category|intval}" {if in_array($category.id_category, $fields_value[$input.name]['category']|default:[])}selected="selected"{/if}>{$category.name}</option>
                    {/foreach}
                </select><br/><br/>
                <p class="alert alert-warning">
                    {l s='If several categories are selected, the product only needs to belong to one of these categories to be flagged.' mod='productflags'}
                </p>
            </div>
            {if count($input.manufacturers)}
            <div class="condition-collapse collapse" id="conditionManufacturer">
                <label for="conditions_manufacturer">{l s='Select a manufacturer' mod='productflags'}</label>
                <select name="conditions[manufacturer]" id="conditions_manufacturer" class="chosen">
                    <option value="0">{l s='--' mod='productflags'}</option>
                    {foreach $input.manufacturers as $manufacturer}
                        <option value="{$manufacturer.id_manufacturer|intval}" {if ($fields_value[$input.name]['manufacturer']|default:0) == $manufacturer.id_manufacturer}selected="selected"{/if}>{$manufacturer.name}</option>
                    {/foreach}
                </select>
            </div>
            {/if}
            {if count($input.suppliers)}
            <div class="condition-collapse collapse" id="conditionSupplier">
                <label for="conditions_supplier">{l s='Select a supplier' mod='productflags'}</label>
                <select name="conditions[supplier]" id="conditions_supplier" class="chosen">
                    <option value="0">{l s='--' mod='productflags'}</option>
                    {foreach $input.suppliers as $supplier}
                        <option value="{$supplier.id_supplier|intval}">{$supplier.name}</option>
                    {/foreach}
                </select>
            </div>
            {/if}
            <div class="condition-collapse collapse  " id="conditionState">
                 <div class="form-group form-group-inline">
                    <label class="required">
                        {l s='Display if product is new' mod='productflags'}
                    </label>
                    <span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="conditions[new]" id="conditions_quantity_new_on" value="1" {if ($fields_value[$input.name]['new']|default:0) == 1}checked="checked"{/if}>
						<label for="conditions_quantity_new_on">{l s='Yes' mod='productflags'}</label>
						<input type="radio" name="conditions[new]" id="conditions_quantity_new_off" value="0" {if ($fields_value[$input.name]['new']|default:0) != 1}checked="checked"{/if}>
						<label for="conditions_quantity_new_off">{l s='No' mod='productflags'}</label>
						<a class="slide-button btn"></a>
					</span>
                </div>
                <div class="form-group form-group-inline">
                    <label class="required">
                        {l s='Display if product is in sale' mod='productflags'}
                    </label>
                    <span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="conditions[sale]" id="conditions_quantity_sale_on" value="1" {if ($fields_value[$input.name]['sale']|default:0) == 1}checked="checked"{/if}>
						<label for="conditions_quantity_sale_on">{l s='Yes' mod='productflags'}</label>
						<input type="radio" name="conditions[sale]" id="conditions_quantity_sale_off" value="0" {if ($fields_value[$input.name]['sale']|default:0) != 1}checked="checked"{/if}>
						<label for="conditions_quantity_sale_off">{l s='No' mod='productflags'}</label>
						<a class="slide-button btn"></a>
					</span>
                </div>
            </div>
            <div class="condition-collapse collapse" id="conditionQuantity">
                <div class="form-group form-group-inline">
                    <label for="conditions_quantity_from">{l s='Quantity from' mod='productflags'}</label>
                    <input id="conditions_quantity_from" type="text" name="conditions[quantity_from]" class="form-control fixed-width-md" value="{$fields_value[$input.name]['quantity_from']|default:''}">
                </div>
                <div class="form-group form-group-inline">
                    <label for="conditions_quantity_to">{l s='Quantity to' mod='productflags'}</label>
                    <input id="conditions_quantity_to" type="text" name="conditions[quantity_to]" class="form-control fixed-width-md" value="{$fields_value[$input.name]['quantity_to']|default:''}">
                </div>
            </div>
            <div class="condition-collapse collapse" id="conditionProduct">
                <div class="form-group">
                    <label for="conditions_include">{l s='Products to include' mod='productflags'}</label>
                    <select name="conditions[include][]" id="conditions_include" class="chosen" multiple="multiple">
                        {foreach $input.products as $product}
                            <option value="{$product.id_product|intval}" {if in_array($product.id_product, $fields_value[$input.name]['include']|default:[])}selected="selected"{/if}>{$product.name}</option>
                        {/foreach}
                    </select>
                    <p class="help-block">
                        {l s='Select additionnals products for which the sticker will be displayed' mod='productflags'}
                    </p>
                </div>
                <div class="form-group">
                    <label for="conditions_exclude">{l s='Products to exclude' mod='productflags'}</label>
                    <select name="conditions[exclude][]" id="conditions_exclude" class="chosen" multiple="multiple">
                        {foreach $input.products as $product}
                            <option value="{$product.id_product|intval}" {if in_array($product.id_product, $fields_value[$input.name]['exclude']|default:[])}selected="selected"{/if}>{$product.name}</option>
                        {/foreach}
                    </select>
                    <p class="help-block">
                        {l s='Select products for which the sticker will not be displayed' mod='productflags'}
                    </p>
                </div>
            </div>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
