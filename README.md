# XenForo2-AdsHelper

Adds the field `$xf.visitor.adsInfo` which allows shipping small amounts of data between templates

```xml
<xf:set var="$xf.visitor.hasOption('canViewAds') && $xf.visitor.canViewAds && $xf.visitor.adsInfo.thing" value="{{ 1 }}" />
```