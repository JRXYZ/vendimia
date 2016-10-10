dropdown = {
    active: null,
    timeout: null,
    
    create: function ( value_id, descr_id, fill_callback ) {
        
        val = Vendimia.id ( value_id ) 
        descr = Vendimia.id ( descr_id )

        // Escondemos el control del valor
        val.type = 'hidden'

        // Con esto buscamos
        descr.onkeyup = dropdown.prepare

        // Y con esto cerramos
        descr.onkeydown = dropdown.keydownEvent

        // Y si perdemos el foco
        descr.onblur = dropdown.blurEvent

        // Por si lo movemos. TODO.
        descr.onmove = function() {}

        // Creamos la ventana del dropdown
        dropdown_id = 'popup_' + value_id

        // Si ya existe, la reusamos
        ddWindow = Vendimia.id ( dropdown_id )
        if ( ! ddWindow ) {
            // Si no existe, la creamos

            ddWindow = Vendimia.c('div', {
                id: dropdown_id,
                class: 'dropdown_container',
            })

            // Empieza oculto.
            ddWindow.style.visibility = 'hidden'

            Vendimia.e('body').appendChild ( ddWindow )
        }

        // Obtenemos la posicion
        pos = Vendimia.xy( descr_id )
        ddWindow.style.left = pos[0] + 'px'
        ddWindow.style.top = ( pos[1] + descr.clientHeight ) + 'px'

        ddWindow.style.width = descr.clientWidth + 'px'

        // Guardamos algo de información en el dropdown
        ddWindow.valuectrl = val
        ddWindow.descrctrl = descr

        // Al objeto de la descripción, le agregamos el dropdown
        descr.dropdown = ddWindow

        // Y de una vez le empujamos el callback
        ddWindow.fill_callback = fill_callback

        // Creamos un pequeño botón de X para cancelar. Lo hacemos aqui abajo
        // para poder añadirle el ddWindow a la X de cerrar.
        button_close = Vendimia.c('button', {
            type: 'button',
            style: 'border: none; background: inherit',
            title: 'Clear field',
            tabindex: -1,
        }, 'X')

        button_close.onclick = function() {
            Vendimia.id ( value_id ).value = ''
            Vendimia.id ( descr_id ).value = ''

            dropdown.close ( ddWindow )
        }   

        if ( descr.nextSibling )
            descr.parentNode.insertBefore ( button_close, descr.nextSibling )
        else
            descr.parentNode.appendChild ( button_close )

        
        return ddWindow
    },

    blurEvent: function (ev) {
        // Si es vacío, entonces borramos el control del valor
        if (this.value == "")
            this.dropdown.valuectrl.value = ""
    },

    keydownEvent: function (ev) {
        ev = ev || window.event

        // El ESC cancela el dropdown, y limpia el control
        if ( ev.keyCode == 27 ) {
            this.dropdown.valuectrl.value = ""
            this.dropdown.descrctrl.value = ""

            dropdown.close ( this.dropdown )
            try { ev.preventDefault() }
            catch (x) { ev.returnValue = false }
        }

        // Inhabilitamos el <ENTER> en este control
        if ( ev.keyCode == 13 ) {
            try { ev.preventDefault() }
            catch (x) { ev.returnValue = false }
        }

        // Borramos la lista
        this.dropdown.innerHTML = ''
    },

    wait: function( ddWindow ) {
        // Mostramos el control
        ddWindow.style.visibility = 'visible'

        // Lo reponemos
        pos = Vendimia.xy ( ddWindow.descrctrl )

        ddWindow.style.left = pos[0] + 'px'
        ddWindow.style.top = ( pos[1] + ddWindow.descrctrl.clientHeight ) + 'px'

        // Le colocamos el iconito de espera.
        ddWindow.innerHTML = '<div style="text-align: center"><img src="assets/imgs/Vendimia_ajax_loading.gif" /></div>'
    },

    close: function ( ddWindow ) {

        // Si no hay un ddWindow, usamos el último
        if ( !ddWindow )
            ddWindow = dropdown.active 

        // Adios
        ddWindow.innerHTML = ''

        // Adios
        ddWindow.value_list = {}

        // Adios
        ddWindow.style.visibility = 'hidden'
    },

    prepare: function (ev) {
        ev = ev || window.event
        kc = ev.keyCode

        ddWindow = this.dropdown

        // No funcionamos en escape
        if ( kc == 27 )
            return

        // Si presionamos enter, entonces aceptamos el primero, de haber
        if ( kc == 13 ) {
            
            // Cancelamos el timer
            if ( dropdown.timeout != null ){
                clearTimeout ( dropdown.timeout )
            }

            // Llamamos al fill_callback primero, aceptando lo 1ro que venga
            ddWindow.fill_callback( true )

            // Evitamos el click
            try { 
                ev.preventDefault() 
            }
            catch (x) { 
                ev.returnValue = false 
            }

            return
        }

        // Esto es inexacto. Solo reaccionamos a estos codigos
        if ( kc < 48 || kc > 122 )
            return
        
        // Hay otro dropdown?
        if ( ddWindow.active ) {
            dropdown.close( dropdown.active )
        }

        // cancelamos otro timeout
        if ( dropdown.timeout != null ){
            clearTimeout ( dropdown.timeout )
        }

        dropdown.timeout = setTimeout( function() {
            // Mostramos el wait
            dropdown.wait( ddWindow )

            // Ahora, llamamos al callback
            ddWindow.fill_callback()

            // Borramos el timeout
            dropdown.timeout = null


        }, 200)

        // Marcamos este como activo
        dropdown.active = ddWindow

    },
    set_values: function( dd, value, descr ) {
        dd.valuectrl.value = value
        dd.descrctrl.value = descr
    },
    onclickEvent: function ( ev ) {
        
        dropdown.set_values ( this.dropdown, this.dropdown_value, this.dropdown_descr )

        // Hay algún callback por llamar?
        if ( this.dropdown.close_callback )
            this.dropdown.close_callback()

        // Nos cerramos
        dropdown.close ( this.dropdown )
    },

    fill: function ( ddWindow, data ) {

        // Limpiamos el dropdown
        ddWindow.innerHTML = ''

        // También borramos el array de objetos
        ddWindow.value_list = {}
        if ( data != {} ) {
            // LLenamos el dropdown
            for ( d in data ) {

                search = ddWindow.descrctrl.value

                texto_puro = data[d]
                texto = texto_puro.replace ( new RegExp(search, "ig"), '<strong>$&</strong>') ;


                el = document.createElement ('div')
                el.className = 'dropdown_option'
                el.innerHTML = texto

                // guardamos información sobre el clic
                el.dropdown_descr = texto_puro
                el.dropdown_value = d
                el.dropdown = ddWindow 

                el.onclick = dropdown.onclickEvent

                ddWindow.appendChild  (el)

                // Vamos guardando en el array de objetos
                ddWindow.value_list [ d ] = texto_puro
            }
        }
        else {
            // Vacio
            div_vacio = Vendimia.c('div', {
                innerHTML: 'Sin resultados',
            })
            div_vacio.style.fontStyle = 'italic'
            div_vacio.style.textAlign = 'center'
            div_vacio.style.color = '#888'

            ddWindow.appendChild ( div_vacio )
        }
    }
}