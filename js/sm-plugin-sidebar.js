( function( wp ) {
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginSidebar = wp.editPost.PluginSidebar;
    var el = wp.element.createElement;
    var smIcon = wp.element.createElement('svg', 
	{ 
		width: 32, 
		height: 32,
                class: 'sm-icon'
	},
	wp.element.createElement( 'path',
            { 
		d: "M21.8,17.2l2.3,1.2l2.3,1.2v2.3L18.2,26c-0.7,0.3-1.4,0.5-2.2,0.5c-0.8,0-1.5-0.2-2.2-0.5l0,0l-8.3-4.1v-2.3l8.3,4.1c0.7,0.3,1.4,0.5,2.2,0.5c0.8,0,1.5-0.2,2.2-0.5l0,0l5.9-3l-2.3-1.2l-3.6,1.8c-0.7,0.3-1.4,0.5-2.2,0.5c-0.8,0-1.5-0.2-2.2-0.5l0,0l-5.9-3l2.3-1.2l3.6,1.8c0.7,0.3,1.4,0.5,2.2,0.5c0.8,0,1.5-0.2,2.2-0.5l0,0L21.8,17.2L21.8,17.2z M16,14.8c0.8,0,1.5,0.2,2.2,0.5l0,0l1.3,0.6l0,0l-1.3,0.7c-0.7,0.3-1.4,0.5-2.2,0.5c-0.8,0-1.5-0.2-2.2-0.5l0,0L12.5,16l1.3-0.7C14.5,15,15.3,14.8,16,14.8L16,14.8z M16,5.5c0.8,0,1.5,0.2,2.2,0.5l0,0l8.3,4.1v2.3l-8.2-4.1C17.5,8,16.8,7.8,16,7.8c-0.8,0-1.5,0.2-2.2,0.5l0,0l-6,3l2.3,1.2l3.6-1.8c0.7-0.3,1.4-0.5,2.2-0.5c0.8,0,1.5,0.2,2.2,0.5l0,0l5.9,3l-2.3,1.2L18.2,13c-0.7-0.3-1.4-0.5-2.2-0.5c-0.8,0-1.5,0.2-2.2,0.5l0,0l-3.6,1.8l-4.7-2.3v-2.3L13.8,6C14.5,5.7,15.2,5.5,16,5.5L16,5.5z",
                fill: "#D81159",
                class: 'letter'
	    }
        )
    );   
 
    registerPlugin( 'content-experience-sidebar', {
        render: function() {
            return el( PluginSidebar,
                {
                    name: 'content-experience-sidebar',
                    icon: smIcon,
                    title: 'Content Experience'
                },
                el('div', 
                    { className: 'sm-sidebar-content' },
                    el('div',
                        {className: 'sm-loader-container'},
                        el('img',
                            {
                                src: 'images/spinner.gif',
                                className: 'sm-loader-img'
                            }
                        )
                    )                    
                )
            );
        },
    } );
} )( window.wp );