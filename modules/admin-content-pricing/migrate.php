<?php 

return [
    'LibUserPerm\\Model\\UserPerm' => [
        'data' => [
            'name' => [
                'read_content_pricing' => ['group'=>'Content Pricing', 'about'=>'Allow user to see self content pricing'],
                'read_content_pricing_all' => ['group'=>'Content Pricing', 'about'=>'Allow user to see all users content pricing'],
                'set_contennt_pricing' => ['group'=>'Content Pricing','about'=>'Allow user to set content pricing'],
                'remove_content_pricing' => ['group'=>'Content Pricing','about'=>'Allow user to remove content pricing']
            ]
        ]
    ]
];