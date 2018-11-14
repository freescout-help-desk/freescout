define(['laroute'], function (Laroute) {

    describe('Laroutes link_to method', function () {

        it('can generate an html link to a url', function () {
            expect(laroute.link_to('foo/bar')).toBe('<a href="/foo/bar" >/foo/bar</a>');
        })

        it('can generate a titled html link to a url', function () {
            expect(laroute.link_to('foo/bar', 'Foo')).toBe('<a href="/foo/bar" >Foo</a>');
        });

        it('can generate an html link to a url with attributes', function () {
            expect(laroute.link_to('foo/bar', 'Foo', { style : 'color:#bada55;' })).toBe('<a href="/foo/bar" style="color:#bada55;">Foo</a>')
        });
    });

});
