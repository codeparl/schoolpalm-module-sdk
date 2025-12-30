import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { Quasar } from 'quasar'
import quasarUserOptions from './quasar-user-options'

import '../css/app.css'

createInertiaApp({
    resolve: name => require(`./Pages/${name}`).default,
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(Quasar, quasarUserOptions)
            .mount(el)
    }
})
