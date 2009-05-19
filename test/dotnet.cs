/** JSONH.pack for ASP.NET
 * @description JSON Homogeneous Collection Packer
 * @version     1.0.1
 * @author      Andrea Giammarchi
 * @license     Mit Style License
 * @project     http://github.com/WebReflection/json.hpack/tree/master
 * @blog        http://webreflection.blogspot.com/
 */

using System;
using System.Collections.Generic;
using System.Web;
using System.Web.Script.Serialization;

public class JSONH
{
    protected static List<List<List<object>>> _cache;

    static public Dictionary<string, object> unpackCreateRow(List<string> keys, List<object> values)
    {
        Dictionary<string, object> result = new Dictionary<string, object>();
        for (int i = 0, len = keys.Count; i < len; i++)
            result[keys[i]] = values[i];
        return result;
    }

    static public int best(List<Dictionary<string, object>> collection)
    {
        JavaScriptSerializer json = new JavaScriptSerializer();
        int j = 0;
        _cache = new List<List<List<object>>>();
        for (int i = 0, len = 0, length = 0; i < 4; i++)
        {
            _cache.Add(pack(collection, i));
            len = json.Serialize(_cache[i]).Length;
            if (length == 0)
                length = len;
            else if (len < length)
            {
                length = len;
                j = i;
            }
        }
        return j;
    }

    static public List<List<object>> pack(List<Dictionary<string, object>> collection)
    {
        return pack(collection, 0);
    }

    static public List<List<object>> pack(List<Dictionary<string, object>> collection, int compression)
    {
        List<List<object>> r = new List<List<object>>();
        if (3 < compression)
        {
            int i = best(collection);
            r = _cache[i];
            _cache.Clear();
        }
        else
        {
            List<List<object>> result = new List<List<object>>();
            List<object> header = new List<object>();
            Dictionary<string, object> first = collection[0];
            int length = collection.Count,
                len = first.Keys.Count,
                index;
            r.Add(header);
            foreach (string key in first.Keys)
                header.Add(key);
            for (int i = 0; i < length; ++i)
            {
                Dictionary<string, object> item = collection[i];
                List<object> row = new List<object>();
                for (int j = 0; j < len; ++j)
                    row.Add(item[(string)header[j]]);
                r.Add(row);
            }
            index = r.Count;
            if (0 < compression)
            {
                List<object> row = r[1];
                for (int j = 0; j < len; ++j)
                {
                    if (!(row[j] is int) && !(row[j] is float) && !(row[j] is double))
                    {
                        List<object> cache = new List<object>(),
                                     current = new List<object>()
                        ;
                        current.Add(header[j]);
                        current.Add(cache);
                        header.RemoveAt(j);
                        header.Insert(j, current);
                        for (int i = 1, k = 0; i < index; ++i)
                        {
                            object value = r[i][j];
                            int l = cache.IndexOf(value);
                            if (l < 0)
                            {
                                cache.Add(value);
                                r[i][j] = k++;
                            }
                            else
                                r[i][j] = l;
                        }
                    }
                }
            }
            if (2 < compression)
            {
                for (int j = 0; j < len; ++j)
                {
                    if (header[j] is List<object>)
                    {
                        JavaScriptSerializer json = new JavaScriptSerializer();
                        List<object> values = new List<object>();
                        List<object> indexes = new List<object>();
                        List<object> cache = (List<object>)header[j];
                        string key = (string)cache[0];
                        cache = (List<object>)cache[1];
                        for (int i = 1; i < index; ++i)
                        {
                            object value = r[i][j];
                            indexes.Add(value);
                            values.Add(cache[(int)value]);
                        }
                        indexes.AddRange(cache);
                        if (json.Serialize(values).Length < json.Serialize(indexes).Length)
                        {
                            for (int k = 0, i = 1; i < index; ++i)
                            {
                                r[i][j] = values[k];
                                ++k;
                            }
                            header[j] = key;
                        }
                    }
                }
            }
            else if (1 < compression)
            {
                length -= (int)Math.Floor((double)(length / 2));
                for (int j = 0; j < len; ++j)
                {
                    if (header[j] is List<object>)
                    {
                        List<object> cache = (List<object>)header[j];
                        string key = (string)cache[0];
                        cache = (List<object>)cache[1];
                        if (length < cache.Count)
                        {
                            for (int i = 1; i < index; ++i)
                            {
                                object value = r[i][j];
                                r[i][j] = cache[(int)value];
                            }
                            header[j] = key;
                        }
                    }
                }
            }
            if (0 < compression)
            {
                for (int j = 0; j < len; ++j)
                {
                    if (header[j] is List<object>)
                    {
                        List<object> cache = (List<object>)header[j];
                        string key = (string)cache[0];
                        header[j] = key;
                        header.Insert(j + 1, cache[1]);
                        ++len;
                        ++j;
                    }
                }
            }
        }
        return r;
    }

    static public List<Dictionary<string, object>> unpack(List<List<object>> collection)
    {
        int length = collection.Count;
        List<object> header = collection[0];
        List<string> keys = new List<string>();
        List<Dictionary<string, object>> result = new List<Dictionary<string, object>>();
        for (int i = 0, k = 0, l = 0, len = header.Count; i < len; ++i)
        {
            keys.Add((string)header[i]);
            k = i + 1;
            if (k < len && header[k] is object[])
            {
                ++i;
                for (int j = 1; j < length; ++j)
                {
                    List<object> row = collection[j];
                    object[] head = (object[])header[k];
                    row[l] = head[(int)row[l]];
                }
            }
            ++l;
        }
        for (int j = 1; j < length; ++j)
            result.Add(unpackCreateRow(keys, collection[j]));
        return result;
    }
}

namespace WebApplication1
{
    public partial class _Default : System.Web.UI.Page
    {
        protected void Page_Load(object sender, EventArgs e)
        {
            string s = "[{\"name\":\"Andrea\",age:31,\"gender\":\"Male\",\"skilled\":true},{\"name\":\"Eva\",\"age\":27,\"gender\":\"Female\",\"skilled\":true},{\"name\":\"Daniele\",\"age\":26,\"gender\":\"Male\",\"skilled\":false}]";
            JavaScriptSerializer json = new JavaScriptSerializer();
            List<Dictionary<string, object>> unpacked = json.Deserialize<List<Dictionary<string, object>>>(s);
            Response.Write(json.Serialize(JSONH.pack(unpacked, 0)).Length);
            Response.Write("<hr />");
            Response.Write(json.Serialize(JSONH.pack(unpacked, 1)).Length);
            Response.Write("<hr />");
            Response.Write(json.Serialize(JSONH.pack(unpacked, 2)).Length);
            Response.Write("<hr />");
            Response.Write(json.Serialize(JSONH.pack(unpacked, 3)).Length);
            Response.Write("<hr />");
            Response.Write(json.Serialize(JSONH.pack(unpacked, 4)).Length);
        }
    }
}
