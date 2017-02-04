define([
  'uiComponent',
  'Magento_Customer/js/customer-data'
], function (Component, customerData) {
  'use strict';

  function loadWidget(callback) {
    var s = document.createElement('script');
    s.async = true;
    s.src = 'http://cdn.belco.dev/widget.js';
    s.onload = s.onreadystatechange = function() {
      var rs = this.readyState;
      if (rs) 
        if (rs != 'complete')
          if (rs != 'loaded') return;

      callback();
    };
    var x = document.getElementsByTagName('script')[0];
    x.parentNode.insertBefore(s, x);
  }

  function initBelco(config) {
    if (config && config.shopId) {
      Belco('init', _.omit(config, 'data_id'));
    }
  }

  return Component.extend({
    initialize: function () {
      var self = this;

      this._super();

      loadWidget(function() {
        var data = customerData.get('belco-config');
        
        data.subscribe(initBelco);

        if (!data().shopId) {
          customerData.reload(['belco-config'], false);
        } else {
          initBelco(data());
        }
      })
    }
  });
});
