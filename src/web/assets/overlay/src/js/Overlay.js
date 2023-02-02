const osimFocusUpdateOverlay = (node, overlay) => {
    const rect = node.getBoundingClientRect()

    overlay.style.left = (window.scrollX + rect.left) + 'px'
    overlay.style.top = (window.scrollY + rect.top) + 'px'
    overlay.style.width = node.offsetWidth + 'px'
    overlay.style.height = node.offsetHeight + 'px'
}

const osimFocusBringIntoView = (node) => {
    const rect = node.getBoundingClientRect()

    let top = window.innerHeight - node.offsetHeight

    if (top < 0) {
        top = window.scrollY + rect.top
    } else {
        top = window.scrollY + rect.top - Math.round(top / 2)
    }

    window.scrollTo(0, top);
}

window.addEventListener('load', () => {
    const params = new URLSearchParams(window.location.search)
    const xpath = params.get('osim-focus-xpath')
    const node = document.evaluate(
        xpath,
        document,
        null,
        XPathResult.FIRST_ORDERED_NODE_TYPE,
        null
    ).singleNodeValue

    if (!node) {
        return
    }

    const overlay = document.createElement('div')
    overlay.className = 'osim-focus-overlay'
    document.body.appendChild(overlay)

    // Monitor document for changes that are not the overlay
    const observer = new MutationObserver((mutationList) => {
        for (const mutation of mutationList) {
            if (mutation.target !== overlay) {
                osimFocusUpdateOverlay(node, overlay)
                osimFocusBringIntoView(node)
                break
            }
        }
    })

    observer.observe(document.body, {
        attributes: true,
        childList: true,
        subtree: true
    })

    // Update overlay on window resize
    window.addEventListener('resize', function(event) {
        osimFocusUpdateOverlay(node, overlay)
        osimFocusBringIntoView(node)
    }, true);

    // Initial positioning of overlay
    osimFocusUpdateOverlay(node, overlay)
    osimFocusBringIntoView(node)
})
