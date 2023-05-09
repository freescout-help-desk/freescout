@extends('layouts.app')
@section('content')
<div class="container">
    <table class="table datatable table-borderless">
        <thead>
            <tr>
                <th class="custom-cell">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="selectAll">
                    </div>
                </th>
                <th class="custom-cell">TICKET NO</th>
                <th class="custom-cell">STATUS</th>
                <th class="custom-cell">PREFERENCE</th>
                <th class="custom-cell">ENGINEER</th>
                <th class="custom-cell">CATEGORY</th>
                <th class="custom-cell">SUBJECT</th>
                <th class="custom-cell">RESOLUTION TIME</th>
                <th class="custom-cell">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="custom-cell">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="">
                        <label class="form-check-label" for="defaultCheck1">
                        </label>
                    </div>
                </td>
                <td class="custom-cell">#1446266</td>
                <td class="custom-cell"><span class="tag tag-open">OPEN</span></td>
                <td class="custom-cell">normal</td>
                <td class="custom-cell">pravin pimpale</td>
                <td class="custom-cell">network</td>
                <td class="custom-cell">Canniset - Changes for Au</td>
                <td class="custom-cell">2 HRS</td>
                <td class="custom-cell">
                    <div class="text-right">
                        <i class="glyphicon glyphicon glyphicon-trash"></i>
                        <i class="glyphicon glyphicon-eye-open"></i>
                        <i class="glyphicon glyphicon-option-vertical"></i>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="custom-cell">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="">
                        <label class="form-check-label" for="defaultCheck1">
                        </label>
                    </div>
                </td>
                <td class="custom-cell">#1446266</td>
                <td class="custom-cell"><span class="tag tag-closed">CLOSED</span></td>
                <td class="custom-cell">normal</td>
                <td class="custom-cell">pravin pimpale</td>
                <td class="custom-cell">network</td>
                <td class="custom-cell">Canniset - Changes for Au</td>
                <td class="custom-cell">2 HRS</td>
                <td class="custom-cell">
                    <div class="text-right">
                        <i class="glyphicon glyphicon glyphicon-trash"></i>
                        <i class="glyphicon glyphicon-eye-open"></i>
                        <i class="glyphicon glyphicon-option-vertical"></i>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="custom-cell">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="">
                        <label class="form-check-label" for="defaultCheck1">
                        </label>
                    </div>
                </td>
                <td class="custom-cell">#1446266</td>
                <td class="custom-cell"><span class="tag tag-hold">HOLD</span></td>
                <td class="custom-cell">normal</td>
                <td class="custom-cell">pravin pimpale</td>
                <td class="custom-cell">network</td>
                <td class="custom-cell">Canniset - Changes for Au</td>
                <td class="custom-cell">2 HRS</td>
                <td class="custom-cell">
                    <div class="text-right">
                        <i class="glyphicon glyphicon glyphicon-trash"></i>
                        <i class="glyphicon glyphicon-eye-open"></i>
                        <i class="glyphicon glyphicon-option-vertical"></i>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="custom-cell">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="">
                        <label class="form-check-label" for="defaultCheck1">
                        </label>
                    </div>
                </td>
                <td class="custom-cell">#1446266</td>
                <td class="custom-cell"><span class="tag tag-open">OPEN</span></td>
                <td class="custom-cell">normal</td>
                <td class="custom-cell">pravin pimpale</td>
                <td class="custom-cell">network</td>
                <td class="custom-cell">Canniset - Changes for Au</td>
                <td class="custom-cell">2 HRS</td>
                <td class="custom-cell">
                    <div class="text-right">
                        <i class="glyphicon glyphicon glyphicon-trash"></i>
                        <i class="glyphicon glyphicon-eye-open"></i>
                        <i class="glyphicon glyphicon-option-vertical"></i>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

</div>
@endsection