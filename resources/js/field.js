Nova.booting((Vue, router, store) => {
  Vue.component('index-location-map-selector', require('./components/IndexField'))
  Vue.component('detail-location-map-selector', require('./components/DetailField'))
  Vue.component('form-location-map-selector', require('./components/FormField'))
})
