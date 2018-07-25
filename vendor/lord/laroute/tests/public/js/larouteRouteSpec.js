define(['laroute'], function (Laroute) {

    describe('Laroutes route method', function() {

        it('can generate a url', function () {
            expect(laroute.route('home')).toBe('/');
        });

        it('can generate a url with named parameters', function () {
            expect(laroute.route('away', { somewhere : 'foo' })).toBe('/away/foo');
            expect(laroute.route('exotic', { somewhere : 'foo', exotic : 'bar' })).toBe('/away/foo/very/bar');
        });

        it('can generate a url with extra parameters as a query string', function () {
            expect(laroute.route('away', { somewhere : 'foo', bat : 'baz' })).toBe('/away/foo?bat=baz');
        });

    });

});
