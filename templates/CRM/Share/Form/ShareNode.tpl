{crmScope extensionKey='de.systopia.share'}
    <div class="crm-block crm-form-block">
        <div class="crm-submit-buttons">
            {include file="CRM/common/formButtons.tpl" location="top"}
        </div>

        {*ADD, UPDATE*}
        {if $action == 1 or $action == 2}
            {foreach from=$elementNames item=elementName}
                <div class="crm-section">
                    <div class="label">{$form.$elementName.label}</div>
                    <div class="content">{$form.$elementName.html}</div>
                    <div class="clear"></div>
                </div>
            {/foreach}
        {/if}

        {*DELETE*}
        {if $action == 8}
            <div class="crm-section no-label">
                <div class="status">
                    <p>{ts 1=$shareNode.short_name}Do you want to delete <em>%1</em>?{/ts}</p>
                    <p class="crm-error">{ts}This action cannot be undone.{/ts}</p>
                </div>
            </div>
        {/if}

        <div class="crm-submit-buttons">
            {include file="CRM/common/formButtons.tpl" location="bottom"}
        </div>
    </div>
{/crmScope}
