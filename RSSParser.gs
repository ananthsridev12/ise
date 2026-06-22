// ============================================================
// RSSParser.gs — Parses Google News RSS XML into plain JS
//                 objects ready for downstream processing.
// ============================================================

var RSSParser = (function() {

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Parses raw RSS XML and returns an array of news item objects.
   * Items are truncated to MAX_RESULTS_PER_QUERY setting.
   *
   * @param {string} xml      Raw RSS/XML string.
   * @param {Object} query    The originating query object.
   * @returns {Object[]}      Array of news item objects.
   */
  function parse(xml, query) {
    if (!xml) return [];

    var maxItems = SettingsManager.getInt('MAX_RESULTS_PER_QUERY', CONST.MAX_RSS_ITEMS);
    var items    = [];

    try {
      var doc      = XmlService.parse(xml);
      var root     = doc.getRootElement();
      var channel  = root.getChild('channel');
      if (!channel) {
        logWarn('RSSParser: no <channel> element in feed for "' + query.queryName + '"');
        return [];
      }

      var rssItems = channel.getChildren('item');
      var limit    = Math.min(rssItems.length, maxItems);

      for (var i = 0; i < limit; i++) {
        var item = rssItems[i];
        var parsed = _parseItem(item, query);
        if (parsed) items.push(parsed);
      }

      logDebug('RSSParser: parsed ' + items.length + ' items for "' + query.queryName + '"');

    } catch(e) {
      logError('RSSParser.parse: XML parse error for "' + query.queryName + '": ' + e.message);
    }

    return items;
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _parseItem(item, query) {
    try {
      var title   = _getText(item, 'title');
      var link    = _getText(item, 'link');
      var pubDate = _getText(item, 'pubDate');
      var desc    = _getText(item, 'description');

      // Google News sometimes nests content in <description> as HTML
      title = stripHtml(title);
      desc  = stripHtml(desc);

      if (!title && !link) return null;

      var publishedDate = pubDate ? new Date(pubDate) : new Date();
      var maxAge        = SettingsManager.getInt('MAX_NEWS_AGE_DAYS', 7);
      if (ageInDays(publishedDate) > maxAge) return null;

      // Source extraction from Google News title suffix " - Source Name"
      var source = _extractSource(title);
      if (source) title = title.replace(' - ' + source, '').trim();

      return {
        title:         truncate(title, 300),
        url:           link  || '',
        snippet:       truncate(desc, 500),
        publishedDate: isoDateTime(publishedDate),
        sourceName:    source || '',
        queryId:       query.queryId,
        queryName:     query.queryName,
        hash:          md5Hash(link || title)
      };

    } catch(e) {
      logWarn('RSSParser._parseItem: failed to parse item: ' + e.message);
      return null;
    }
  }

  function _getText(element, tagName) {
    try {
      var child = element.getChild(tagName);
      return child ? child.getText() : '';
    } catch(e) {
      return '';
    }
  }

  function _extractSource(title) {
    // Google News appends " - Publisher Name" at end of title
    var match = title.match(/ - ([^-]+)$/);
    return match ? match[1].trim() : '';
  }

  return { parse: parse };

})();
