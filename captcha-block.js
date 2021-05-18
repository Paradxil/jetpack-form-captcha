( function ( blocks, element, blockEditor ) {
    var el = element.createElement;
    var useBlockProps = blockEditor.useBlockProps;
 
    var blockStyle = {
        color: '#444',
        padding: '20px',
        boxSizing: 'border-box',
        borderRadius: '2px',
        border: '1px solid #999',
        marginBottom: '15px',
        marginRight: '15px',
    };
    
 
    blocks.registerBlockType( 'jetpack/captcha', {
        apiVersion: 2,
        title: 'Captcha',
        icon: 'lock',
        example: {},
        parent: [ 'jetpack/contact-form' ],
        edit: function () {
            var blockProps = useBlockProps( { style: blockStyle } );
            return el(
                'div',
                blockProps,
                'Captcha Field'
            );
        },
        save: function () {
            var blockProps = useBlockProps.save( {class: 'icon-captcha' } );
            return el(
                'div',
                blockProps,
                ''
            );
        },
    } );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor );