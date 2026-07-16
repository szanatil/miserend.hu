# miserend.hu

A Hungarian Catholic platform where users search for nearby churches and Mass schedules, and church administrators manage service times.

## Language

**Mass** (Mise):
A Catholic liturgical celebration held at a church on a recurring or one-off schedule.
_Avoid_: service, event, liturgy (use only when referring to the general category)

**Church** (Templom):
A Catholic place of worship that owns one or more Mass schedules.
_Avoid_: location, venue, parish (parish is a separate administrative unit)

**Mass Schedule** (Miserend):
The set of recurring and one-off Masses associated with a Church.
_Avoid_: timetable, calendar entries, service times

**Liturgical Language**:
The language in which a Mass is celebrated, identified by a 2–3 character code (e.g. `hu`, `ru`). One Mass has exactly one liturgical language. The code drives flag icon display across both the Angular calendar and legacy PHP search results.
_Avoid_: mass language, lang, language tag

**Language Code**:
A short lowercase identifier for a liturgical language, matching ISO 639-1 where applicable (e.g. `hu`, `en`, `ru`). The authoritative list on the frontend is the `LanguageCode` TypeScript enum; the database column `cal_masses.lang` is unconstrained `varchar(3)` with no server-side validation.
_Avoid_: lang code, language abbreviation, locale
