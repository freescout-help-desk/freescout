define(['laroute'], function (Laroute) {

    describe('Laroutes action method', function () {

        it('can generate a url', function () {
            expect(laroute.action('HomeController@index')).toBe('/');
        });

        it('can generate a url with named parameters', function () {
            expect(laroute.action('AwayController@somewhere', { somewhere : 'foo' })).toBe('/away/foo');
        });

        it('can generate a url with named parameters as a query string', function () {
            expect(laroute.action('AwayController@somewhere', { somewhere : 'foo', bat : 'baz' })).toBe('/away/foo?bat=baz');
        });

    });

});
