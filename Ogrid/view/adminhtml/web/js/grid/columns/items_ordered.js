define([

    'underscore',
    'mageUtils',
    'uiLayout',
    'uiCollection',
	'jquery',
	'Magento_Ui/js/modal/modal',
     'mage/url',


    //'Magento_Ui/js/grid/columns/column'
], function (_, utils, layout, Collection,$,modal,url) {
    'use strict';

   var count1 = 0; 


   var filterTextVal ="";
   var ncoounts =0;
    return Collection.extend({
        defaults: {
            headerTmpl: 'ui/grid/columns/text',
            bodyTmpl: 'Amasty_Ogrid/ui/grid/cells/items_ordered',
            disableAction: true,
            controlVisibility: true,
            sortable: true,
            sorting: false,
            visible: true,
            draggable: true,
            columns:{
                base: {
                    parent: '${ $.name }',
                    component: 'Magento_Ui/js/grid/columns/column',
                    bodyTmpl: 'Amasty_Ogrid/ui/grid/cells/text',
                    headerTmpl: 'Amasty_Ogrid/ui/grid/columns/text',
                    filter: 'text',
                    defaults: {
                        draggable: false,
                        sortable: false,
                    },
                    initObservable: function () {
                        console.log("footer ");
                        this._super()
                            .track([
                                'visible',
                                'sorting',
                                'disableAction',
                                'subVisible',
                                'label'
                            ])
                            .observe([
                                'dragging'
                            ]);

                        return this;
                    },
                },
                thumbnail: {
                    component: 'Magento_Ui/js/grid/columns/thumbnail',
                    bodyTmpl: 'Amasty_Ogrid/ui/grid/cells/thumbnail',
                    has_preview: true
                },
				flags:{
				component: 'Otta_OttaVendor/js/grid/columns/flag',
			                        isAssignAllowed: false
				}
            },
			
            imports: {
                productCols: '${ $.columnsControlsProvider }:productCols'
            },
            modules: {
                listingFilters: '${ $.listingFiltersProvider }'
            },
            listens: {
                productCols: 'updateProductCols',
                elems: 'updateFilters'
            }
        },
        initElement: function (el) {
            el.track(['label', 'subVisible'])

        },
        initialize: function () {
            console.log("initialize");
            this._super();
			    
            return this;
        },
        updateFilters: function() {

            console.log("updateFilters");
            _.each(this.elems(), function(column) {
                if (column.filter) {
                    column.visible = column.subVisible;
                    column.label = column.amogrid_label;
                    var thisFilter = this.listingFilters().buildFilter(column);
                    layout([thisFilter]);
                }

            }.bind(this))
			
        },
        updateProductCols: function() {
 

            console.log("updateProductCols");
            _.each(this.getVisibleCols(), function (col) {
			
                var config = utils.extend({}, this.columns.base, {
                    name: col.index,
                    subVisible: col.visible,
                    visible: col.visible,
                    amogrid_label: col.amogrid_label
                });

                if (col.productAttribute && col.frontendInput == 'media_image') {
					//console.log(config);
                    config = utils.extend({}, config, this.columns.thumbnail);
					
                }
				

                var component = utils.template(config, {
					


                });

                layout([component]);

            }.bind(this));

            _.each(this.elems(), function(elem) {
                _.each(this.productCols, function(col) {
                    if (elem.index === col.index) {
                        elem.visible = col.visible;
                        elem.subVisible = col.visible;
                        elem.amogrid_label = col.amogrid_label;
                    }

                })
            }.bind(this));
			

        },

        initObservable: function () {
            this._super()
                .track([
                    'visible',
                    'sorting',
                    'disableAction',
                    'productCols'
                ])
                .observe([
                    'dragging'
                ]);

            return this;
        },
        initFieldClass: function () {
            _.extend(this.fieldClass, {
                _dragging: this.dragging
            });

            return this;
        },
        getVisibleCols: function() {
            return _.filter(this.productCols, function(el) {
                return el.visible === true;
            });

        },
        getColumns: function(){

            console.log("Get Colums Fn");
            return this.elems.filter('subVisible');
        },
        getItems: function(record) {
           
      
                          console.log('flaclass'); 
            var test = $( ".admin__current-filters-list-wrap li span:nth-child(2)" ).html();

             

                if(count1 == 0)
                 { 

                     setTimeout(function() {


                       jQuery(".action-default").click(function()
                       {
                    count1 =0;
                    console.log("admincontrioll");
                    jQuery(".admin__control-text").blur(function()
                    {
                        count1 =0;
                    });

                    jQuery(".admin__control-select").change(function()
                    {
                        count1 =0;
                    });

                    });
                      $('.flaclass').click(function()
                        {
                          console.log('flaclass'); 
                       $(".otta-flags-assign .flags-container").html('');
                     
                          var oid = $(this).attr('orderid');
                          var productid = $(this).attr('productid');
                          var columnid = $(this).attr('columnid');


                                       var options = {
                                              type: 'popup',
                                              responsive: true,
                                              innerScroll: true,
                                              title: 'Products Flags'
                                          };          
                          var actionurl = url.build('/yonetim/otta_ottavendor/flacolumajax/ordergridflags/');
                      var payload = {
                                      oid: oid,
                                      productid: productid,
                                      columnid: columnid
                                  };
                               // $('body').trigger('processStart');
                     
                                  $.ajax({
                                      url: actionurl,
                                      type: 'POST',
                                      data: payload
                                  }).done(function (response) {
                                   
                                     // $('body').trigger('processStop');
                                      $(".otta-flags-assign .flags-container").html(response);

                                     
                                  });
       $(".sales_order_grid_sales_order_grid_otta_flag_assign .modal-content").modal(options).modal('openModal');
        var modlcon = $(".sales_order_grid_sales_order_grid_otta_flag_assign").attr("aria-describedby");
                               $("#"+modlcon).modal(options).modal('openModal');
                               $('body').trigger('processStart');
                                    setTimeout(function() {
                                  console.log("testssurinder");
                                 
                         $('.flagset').click(function()
                         {
                             console.log('.flagset');
                         var modlcon = $(".sales_order_grid_sales_order_grid_otta_flag_assign").attr("aria-describedby");

                          $("#"+modlcon).modal(options).modal('closeModal');
                     
                             var oid = $(this).attr('ordereid');
                          var productid = $(this).attr('proid');
                          var columnid = $(this).attr('columnid');
                              var flagid = $(this).attr('flagid');
                                 var flagnotes = $('.note'+oid+flagid+columnid+productid).val();
                                  var payload = {
                                       orderId: oid,
                                      columnId:columnid,
                                      flag: flagid,
                                      flags: flagid,
                                      productid:productid,
                                      note:flagnotes
                                  };

                                  $('body').trigger('processStart');
                                      var actionurls = url.build('/yonetim/otta_ottavendor/flagAssign/assign/');
                                  // do not use "success" method because some error responses might be with 200 code
                                  return $.ajax({
                                      url: actionurls,
                                      type: 'POST',
                                      data: payload
                                  }).done(function (response) {
                                      $('body').trigger('processStop');
                                      if (response && response.hasOwnProperty('error')) {
                                          alert({content: response.message || $t('Unknown error')});
                                      } else {
                                          // var src = flag ? flag.image_src : self.imagePlaceholder;
                                          // var title = self.columns[self.currentColumnId].comment || $t('No flag');
                    
                                          // if (flag) {
                                              // title = flag.note || flag.defaultNote || flag.title;
                                          // }
                                        
                                          if(flagid == '')
                                          {
                                              var flaimg = 'empty';
                                          }
                                          else
                                          {
                                              var flaimg= flagid;
                                             
                                          }   
                     
                                  var imaeurls = url.build('/pub/media/otta/flags/')+flaimg+'.png';
                                          $('#otta-flag-'+oid+'-'+productid+'-'+columnid).attr('src',imaeurls);
                                         
                                        
                                      }
                                  }).fail(function (response) {
                                      $('body').trigger('processStop');
                                      alert({content: $t('Unknown error')});
                                  });
                            
                            
                         });
                         $('body').trigger('processStop');
                     
                         }, 4000);
                            
                         });
                      console.log('yes count 0');  

          // $('body').trigger('processStart');

                         },4000);
                         count1 = 2;
                         filterTextVal =test; 
                        // alert(count1);

                 }

               
                

			
            var rows = [];
            var orderData = record[this.index];
            return _.map(orderData);
        },
        getFieldClass: function () {},
        getHeader: function () {
          
            return this.headerTmpl;
        },
        getBody: function () {

            return this.bodyTmpl;
        },
        sort: function (enable) {},
        getFieldHandler: function () 
		{


		}
    });

});