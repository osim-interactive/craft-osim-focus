if (typeof Craft.OsimFocus === typeof undefined) {
    Craft.OsimFocus = {}
}

Craft.OsimFocus.PageIndex = Craft.BaseElementIndex.extend({
    init: function (elementType, $container, settings) {
        this.on('selectSource', this.updateUrl.bind(this));
        this.on('selectSite', this.updateUrl.bind(this));
        this.base(elementType, $container, settings);
    },

    getDefaultSourceKey: function() {
        if (this.settings.context === 'index') {
            for (let i = 0; i < this.$sources.length; i++) {
                const $source = $(this.$sources[i]);
                const projectId = ($source.data('projectid') || '').toString()

                if (projectId == osimFocusProjectId) {
                    return $source.data('key')
                }
            }
        }

        return this.base()
    },

    updateUrl: function() {
        if (!this.$source) {
            return;
        }

        if (this.settings.context === 'index') {
            let url = 'osim-focus/pages';

            const projectId = this.$source.data('projectid')

            if (projectId) {
                url += '/projects/' + projectId
            }

            Craft.setPath(url)
        }
    }
})

Craft.registerElementIndexClass(
    'osim\\craft\\focus\\elements\\Page',
    Craft.OsimFocus.PageIndex
)
