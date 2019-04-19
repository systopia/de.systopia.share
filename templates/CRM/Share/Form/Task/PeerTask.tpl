{*-------------------------------------------------------+
| SYSTOPIA Rebook Extension                              |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+-------------------------------------------------------*}

<div class="crm-form-block crm-block crm-contact-task-pdf-form-block">
    <br/>
    <div>
        <div class="crm-section">
            <div class="label">{$form.node_id.label}</div>
            <div class="content">{$form.node_id.html}</div>
            <div class="clear"></div>
        </div>
    </div>

    <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>
