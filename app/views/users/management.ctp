Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '/WebHive/ext/ux/');

Ext.require([
        'Ext.form.field.ComboBox',
        'Ext.container.*',
        'Ext.grid.*',
        'Ext.data.*',
        'Ext.util.*',
        'Ext.ProgressBar.*',
        'Ext.state.*',
        'Ext.window.Window',
        'Ext.ux.FieldReplicator'
]);

Ext.onReady(function() {

        var inputQuery = Ext.create('Ext.Panel', {
                xtype:'form',
                layout: 'column',
                border: false,
                width: 620,
                items:[{
                        id: 'inDatabase',
                        xtype: 'combo',
                        store: storeDatabase,
                        fieldLabel: 'Database',
                        value:'default',
                        width: 300,
                        editable: false,
                        triggerAction: 'all',
                        mode: 'local',
                        valueField: "id",
                        displayField: "caption",
                        queryMode: 'local',
                        typeAhead: true
                }]
        });


        ///////////////////////////////////////////////////////////////////
        // Viewport設定
        ///////////////////////////////////////////////////////////////////
        Ext.create('Ext.container.Viewport', {
                layout: 'border',
                renderTo: Ext.getBody(),
                items:[{
                        items: inputQuery,
                        region: 'center',
                        split: true,
                        height: '55%',
                        width: '50%'
                }]
        });

});
