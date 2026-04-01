/* global wp */

/**
 * ShopSavvy Price Comparison — Gutenberg editor component.
 *
 * Renders block controls for configuring the product identifier,
 * max results, and display style within the block editor.
 */
( function () {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const {
        PanelBody,
        TextControl,
        RangeControl,
        SelectControl,
        Placeholder,
        Spinner,
    } = wp.components;
    const { useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    const apiFetch = wp.apiFetch;

    registerBlockType( 'shopsavvy/price-comparison', {
        edit: function ShopSavvyEdit( { attributes, setAttributes } ) {
            const { identifier, maxResults, style } = attributes;
            const blockProps = useBlockProps();

            const [ preview, setPreview ] = useState( null );
            const [ loading, setLoading ] = useState( false );
            const [ error, setError ] = useState( '' );

            // Fetch preview when identifier changes.
            useEffect( function () {
                if ( ! identifier ) {
                    setPreview( null );
                    setError( '' );
                    return;
                }

                setLoading( true );
                setError( '' );

                // Use the REST endpoint to fetch a preview if available,
                // otherwise fall back to rendering server-side.
                apiFetch( {
                    path: '/shopsavvy/v1/search?id=' + encodeURIComponent( identifier ) + '&type=offers&max=' + maxResults,
                } )
                    .then( function ( data ) {
                        setPreview( data );
                        setLoading( false );
                    } )
                    .catch( function ( err ) {
                        setPreview( null );
                        setError( err.message || __( 'Failed to load preview.', 'shopsavvy' ) );
                        setLoading( false );
                    } );
            }, [ identifier, maxResults ] );

            /**
             * Render a simple preview table in the editor.
             */
            function renderPreview() {
                if ( loading ) {
                    return wp.element.createElement(
                        'div',
                        { style: { textAlign: 'center', padding: '20px' } },
                        wp.element.createElement( Spinner, null ),
                        wp.element.createElement( 'p', null, __( 'Loading prices...', 'shopsavvy' ) )
                    );
                }

                if ( error ) {
                    return wp.element.createElement(
                        'p',
                        { className: 'shopsavvy-error' },
                        error
                    );
                }

                if ( ! preview || ! preview.offers || ! preview.offers.length ) {
                    return wp.element.createElement(
                        'p',
                        { style: { color: '#999', fontStyle: 'italic' } },
                        __( 'No offers to preview. The block will render on the front end.', 'shopsavvy' )
                    );
                }

                var rows = preview.offers.map( function ( offer, i ) {
                    return wp.element.createElement(
                        'tr',
                        { key: i },
                        wp.element.createElement( 'td', null, offer.retailer_name || '—' ),
                        wp.element.createElement( 'td', null, offer.price ? ( '$' + Number( offer.price ).toFixed( 2 ) ) : '—' ),
                        wp.element.createElement( 'td', null, offer.condition || '' )
                    );
                } );

                return wp.element.createElement(
                    'table',
                    { className: 'shopsavvy-table', style: { width: '100%' } },
                    wp.element.createElement(
                        'thead',
                        null,
                        wp.element.createElement(
                            'tr',
                            null,
                            wp.element.createElement( 'th', null, __( 'Retailer', 'shopsavvy' ) ),
                            wp.element.createElement( 'th', null, __( 'Price', 'shopsavvy' ) ),
                            wp.element.createElement( 'th', null, __( 'Condition', 'shopsavvy' ) )
                        )
                    ),
                    wp.element.createElement( 'tbody', null, rows )
                );
            }

            return wp.element.createElement(
                'div',
                blockProps,
                // Inspector sidebar controls.
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(
                        PanelBody,
                        { title: __( 'ShopSavvy Settings', 'shopsavvy' ), initialOpen: true },
                        wp.element.createElement( TextControl, {
                            label: __( 'Product Identifier', 'shopsavvy' ),
                            help: __( 'Enter a UPC, EAN, ASIN, URL, model number, or MPN.', 'shopsavvy' ),
                            value: identifier,
                            onChange: function ( val ) {
                                setAttributes( { identifier: val } );
                            },
                        } ),
                        wp.element.createElement( RangeControl, {
                            label: __( 'Max Results', 'shopsavvy' ),
                            value: maxResults,
                            onChange: function ( val ) {
                                setAttributes( { maxResults: val } );
                            },
                            min: 1,
                            max: 20,
                        } ),
                        wp.element.createElement( SelectControl, {
                            label: __( 'Display Style', 'shopsavvy' ),
                            value: style,
                            options: [
                                { label: __( 'Table', 'shopsavvy' ), value: 'table' },
                                { label: __( 'Card', 'shopsavvy' ), value: 'card' },
                                { label: __( 'Inline', 'shopsavvy' ), value: 'inline' },
                            ],
                            onChange: function ( val ) {
                                setAttributes( { style: val } );
                            },
                        } )
                    )
                ),
                // Block content area.
                ! identifier
                    ? wp.element.createElement(
                          Placeholder,
                          {
                              icon: 'chart-bar',
                              label: __( 'ShopSavvy Price Comparison', 'shopsavvy' ),
                              instructions: __( 'Enter a product identifier in the block settings to display price comparisons.', 'shopsavvy' ),
                          },
                          wp.element.createElement( TextControl, {
                              placeholder: __( 'e.g. B08N5WRWNW or a UPC barcode', 'shopsavvy' ),
                              value: identifier,
                              onChange: function ( val ) {
                                  setAttributes( { identifier: val } );
                              },
                          } )
                      )
                    : wp.element.createElement(
                          'div',
                          { className: 'shopsavvy-block-preview' },
                          wp.element.createElement(
                              'div',
                              { className: 'shopsavvy-block-header' },
                              wp.element.createElement(
                                  'span',
                                  { className: 'dashicons dashicons-chart-bar', style: { marginRight: '8px' } }
                              ),
                              wp.element.createElement(
                                  'strong',
                                  null,
                                  __( 'ShopSavvy Price Comparison', 'shopsavvy' )
                              ),
                              wp.element.createElement(
                                  'code',
                                  { style: { marginLeft: '8px', fontSize: '12px' } },
                                  identifier
                              )
                          ),
                          renderPreview()
                      )
            );
        },

        // Server-side rendering — no save output needed.
        save: function () {
            return null;
        },
    } );
} )();
