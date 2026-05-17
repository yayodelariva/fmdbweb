(function () {
    'use strict';

    var replacements = [
        [ 'Add coupons',     'Agregar cupones'  ],
        [ 'Free shipping',   'Envío gratis'     ],
        [ 'Estimated total', 'Total estimado'   ],
        [ 'Coupon code',     'Código de cupón'  ],
        [ 'Apply',           'Aplicar'          ],
    ];

    function patchText(root) {
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null, false);
        var node;
        while ((node = walker.nextNode())) {
            var val = node.nodeValue;
            for (var i = 0; i < replacements.length; i++) {
                if (val === replacements[i][0]) {
                    node.nodeValue = replacements[i][1];
                    break;
                }
            }
        }
    }

    function init() {
        var cart = document.querySelector('.wc-block-cart, .wp-block-woocommerce-cart');
        if (!cart) return;

        patchText(cart);

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (n) {
                    if (n.nodeType === Node.ELEMENT_NODE) patchText(n);
                });
                if (m.type === 'characterData') {
                    var val = m.target.nodeValue;
                    for (var i = 0; i < replacements.length; i++) {
                        if (val === replacements[i][0]) {
                            m.target.nodeValue = replacements[i][1];
                            break;
                        }
                    }
                }
            });
        });

        observer.observe(cart, { childList: true, subtree: true, characterData: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
