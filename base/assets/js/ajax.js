V.ajax = {
    OK: 0,
    REDIRECT: -1,
    MESSAGE: -255,
    EXCEPTION: -65535,
	req: {},

    /**
     * Shows a message using #vendimia_ajax_message or window.alert() 
     */
	message: function (message, title = '')
    {
	    div = V.id('vendimia_ajax_message');

	    // Si no hay div, usamos un window.alert 
	    if (!div) {
	        window.alert (message)
	        return false
	    }

	    if (message == false) {
	        div.style.display = 'none';
	        return true;
	    }
	    
	    // Mostramos siempre el mensaje    
	    div.style.display = 'block';
	    
	    div.innerHTML = msg;
	},

    /**
     * Shows a exception in debug mode
     */
     exception: function(data) {
        // TODO
        console.log(data)
        window.alert("EXCEPTION")
     },

    /** 
     * Enables o disables the #vendimia_ajax_progress element.
     */
    progress: function (active)
    {
        progress = V.id('vendimia_ajax_progress')

        if ( progress ) {
            if ( active ) {
                progress.style.visibility = 'visible';
            } else {
                progress.style.visibility = 'hidden';
            }
        }
    },

    /**
     * Executes the AJAX call
     */
	call: function (method, url, vars, callback)
    {
        var req = new XMLHttpRequest()

        // De repente no hay variables, y es el callback
        if (typeof(vars) == "function") {
            callback = vars
            vars = {}
        }

	    // Convertimos las variables en un string
	    res = [];
	    for ( v in vars ) {
            // Los arrays lo tratamos distinto
            if (vars[v] instanceof Array) {
                for (d in vars[v]) {
                    res.push ( v + '[]=' + encodeURIComponent ( vars[v][d] ) );
                }
            }
            else
              res.push ( v + '=' + encodeURIComponent ( vars[v] ) );
	    }
	    vars = res.join ('&')

	    // Si llamamos por GET, las variables las ponemos en la URL
	    if ( method == "GET" ) {
	    	url += '?' + vars;
        }

	    // Obtenemos la ruta base. Si no tiene schema, agregamos la base
	    re = /^.*:\/\//
		if (!re.exec (url)) {
			url = V.URLBASE + url
        }

        // Activamos el div del progress
        V.ajax.progress (true)

	    req.open (method, url, true)

    	// Marca de AJAX
	    req.setRequestHeader('X-Vendimia-Requested-With', 'XmlHttpRequest');

        // Le añadimos el Token de CSRF
        req.setRequestHeader('X-Vendimia-Security-Token', 
            V.e("meta[name=Vendimia-security-token]").content);

	    // La información va urlencodeadea
	    req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	    
	    /*
        // DEPRECATED
        req.onreadystatechange = function (data) {

	        if (this.readyState == 4) {
                // Desactivamos el progress
                V.ajax.progress ( false )
	            try {
                    if (JSON.parse) {
                        data = JSON.parse ( this.responseText )
                    } else {
                        data = new Function('return ' + this.responseText + ';')();
                    }

	                // Nos fijamos si viene algun código de error
	                switch ( data.__CODE ) {
	                case -255: // Message
	                    V.ajax.message (data.__MESSAGE)
                        break
                    case -65535:
                        // Execption!
                        d = V.c('div');
                        d.style.position = 'fixed';
                        d.style.top = "50px"
                        d.style.left = "50px"
                        d.style.width = (document.width - 100)  + "px"
                        d.style.height = (document.height - 100) + "px"
                        d.style.background = 'white'
                        d.style.border = "1px solid black"
                        d.style.padding = "10px";
                        d.style.zIndex = "100";

                        V.e('body').appendChild ( d )

                        d.innerHTML = data.__MESSAGE

                        console.log ( data.__MESSAGE )
                        console.log ( d )
                    }

	                // retornamos el array data a la funcion callback
	                callback (data);
	                
	                return;
	            }
	            catch (e) {
	                //ajax.message ('¡Error inesperado!');

	                console.log ('ERROR ' + e.name + ": " + e.message );
	                console.log ( e.stack );
	                console.log( {responseText : this.responseText});
	                return;
	            }
	        }
	    }*/
        req.onload = function() {
            try {
                payload = JSON.parse(this.responseText)
            } catch (e) {
                V.ajax.exception(e)
            }

            // Viene un código?
            switch (payload.__CODE) {
            case V.MESSAGE: // Message
                V.ajax.message(payload.__MESSAGE)
                break
            case V.EXCEPTION:
                V.ajax.exception(payload)
                break
            }
            // Ejecutamos el callback
            callback(payload)
            return
        }

        // Enviamos el query
	    req.send (vars);
	},

	post: function (url, vars, callback)
    {
		V.ajax.call ( 'POST', url, vars, callback );
	},

	get: function (url, vars, callback)
    {
		V.ajax.call ( 'GET', url, vars, callback );
	},

    /**
     * Prepara un formulario para ser enviado por ajax.
     */
    prepare: function (form, callback)
    {
        if (typeof form == "string") {
            var f = V.id(form)
        } else {
            var f = form 
        }
        
        f.onsubmit = V.ajax.submit
        f.callback = callback
    },


    submit:function(evt)
    {

        //Evitamos que el formulario funcione
        evt.preventDefault()

        form = this;

        // Convertimos cada elemento del formulario para enviarlo por ajax
        vars = {}
        for ( var i = 0; i < form.elements.length; i++ ) {
            el = form.elements [ i ]


            // Según el tipo de control, sacamos su valor.
            value = null
            switch ( el.nodeName.toLowerCase() ) {
            case 'input':
                switch ( el.type ) {
                case 'checkbox':
                    // El checkbox es algo especial
                    value = el.checked?el.value:''
                    break;
                default:
                    value = el.value
                }
                break;
            default:
                value = el.value
            }

            if ( value ) {
                // Si ya existe este elemento, lo añadimos al array
                if ( vars [ el.name ] ) {

                    // Primero, tenemos que convertirlo en un array, si
                    // no lo es
                    if ( ! ( vars [ el.name ] instanceof Array) ) {
                        vars [ el.name ] = [ vars [ el.name ] ]
                    }

                    vars [ el.name ].push  ( el.value  )
                } else {
                    vars [ el.name ] = el.value 
                }
            }
        }

        // Si no hay action, usamos la URL actual
        if ( !form.action ) {
            form.action = document.location.href
        }

        V.ajax.call ( form.method, form.action, vars, form.callback )
    },
}
